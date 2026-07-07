<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentPackage;
use App\Services\PackageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        private PackageService $packageService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teacher = Auth::user()->teacher;
        $monthParam = request('month', now()->month);
        // "all" (or 0) means show every past & current class, ignoring month/year.
        $showAll = in_array((string) $monthParam, ['all', '0'], true);
        $month = $showAll ? 'all' : (int) $monthParam;
        $year = (int) request('year', now()->year);
        $studentId = request('student_id');

        // Shared closure so the list query and the totals query stay in sync.
        $applyScope = function ($query) use ($studentId, $showAll, $year, $month) {
            if ($studentId) {
                // Student selected: show lessons from their active/pending packages
                $packageIds = StudentPackage::where('student_id', $studentId)
                    ->whereIn('status', ['active', 'completed'])
                    ->pluck('id');

                $query->where('student_id', $studentId)
                    ->whereIn('student_package_id', $packageIds);
            } elseif (! $showAll) {
                // Apply month/year filter only when not viewing all classes
                $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
            }
            // else: no date filter — show all of this teacher's classes

            return $query;
        };

        $lessons = $applyScope(
            Lesson::with(['student.currentPackage','studentPackage'])
                ->where('teacher_id', $teacher->id)
        )->latest('date')->paginate(20)->withQueryString();

        $totalMinutes = $applyScope(
            Lesson::where('teacher_id', $teacher->id)
        )->sum('duration_minutes');
        
        // Get all students assigned to this teacher
        $students = Student::active()
            ->where('assigned_teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();
        
        return view('teacher.lessons.index', [
            'lessons' => $lessons,
            'totalMinutes' => $totalMinutes,
            'month' => $month,
            'year' => $year,
            'students' => $students,
            'studentId' => $studentId,
            'showAll' => $showAll,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teacher = Auth::user()->teacher;
        $students = Student::active()
            ->where('assigned_teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();
        return view('teacher.lessons.create', compact('students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $teacher = Auth::user()->teacher;
        $validated = $request->validate([
            'student_id' => ['required','exists:students,id'],
            'hours' => ['required','integer','min:0','max:8'],
            'minutes' => ['required','integer','min:0','max:59'],
            'date' => ['required','date','before_or_equal:today'],
            'status' => ['required','in:attended,absent_student,absent_teacher,cancelled_student,cancelled_teacher,trial'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if ($student->assigned_teacher_id !== $teacher->id) {
            abort(403);
        }

        $durationMinutes = ((int)$validated['hours'] * 60) + (int)$validated['minutes'];

        try {
            $lesson = Lesson::create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'duration_minutes' => $durationMinutes,
                'date' => $validated['date'],
                'status' => $validated['status'],
                'is_trial' => $validated['status'] === 'trial',
            ]);

            $student->refresh();

            // Get or create the monthly package for this lesson's month
            $monthlyPackage = $this->packageService->getOrCreateMonthlyPackage(
                $student,
                \Carbon\Carbon::parse($validated['date'])
            );

            $this->packageService->assignLessonToPackage($lesson, $monthlyPackage);
            
            // Refresh lesson to get updated package info
            $lesson->refresh();
            
            $student->recalculateHoursTaken();

            $message = $lesson->is_pending 
                ? 'Lesson added and marked as pending (package exhausted).' 
                : 'Lesson added.';

            return redirect()->route('teacher.lessons.index')->with('status', $message);
        } catch (\Exception $e) {
            Log::error('Error creating lesson: ' . $e->getMessage(), [
                'student_id' => $validated['student_id'],
                'teacher_id' => $teacher->id,
                'exception' => $e
            ]);
            return back()->withInput()->withErrors(['error' => 'Error creating lesson: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson)
    {
        $teacher = Auth::user()->teacher;
        abort_if($lesson->teacher_id !== $teacher->id, 403);
        $students = Student::active()
            ->where('assigned_teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();
        return view('teacher.lessons.edit', compact('lesson','students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $teacher = Auth::user()->teacher;
        abort_if($lesson->teacher_id !== $teacher->id, 403);
        $validated = $request->validate([
            'student_id' => ['required','exists:students,id'],
            'hours' => ['required','integer','min:0','max:8'],
            'minutes' => ['required','integer','min:0','max:59'],
            'date' => ['required','date','before_or_equal:today'],
            'status' => ['required','in:attended,absent_student,absent_teacher,cancelled_student,cancelled_teacher,trial'],
        ]);
        $student = Student::findOrFail($validated['student_id']);
        abort_if($student->assigned_teacher_id !== $teacher->id, 403);

        $durationMinutes = ((int)$validated['hours'] * 60) + (int)$validated['minutes'];
        $lesson->update([
            'student_id' => $student->id,
            'duration_minutes' => $durationMinutes,
            'date' => $validated['date'],
            'status' => $validated['status'],
            'is_trial' => $validated['is_trial'] ?? false,
        ]);

        $student->refresh();

        // Always resolve the correct monthly package for the lesson's (possibly new) date
        $monthlyPackage = $this->packageService->getOrCreateMonthlyPackage(
            $student,
            \Carbon\Carbon::parse($validated['date'])
        );

        if ($lesson->student_package_id !== $monthlyPackage->id) {
            $this->packageService->assignLessonToPackage($lesson, $monthlyPackage);
        } else {
            $this->packageService->recalculatePackageLessons($monthlyPackage);
        }
        
        $student->recalculateHoursTaken();
        return redirect()->route('teacher.lessons.index')->with('status', 'Lesson updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson)
    {
        try {
            $teacher = Auth::user()->teacher;
            abort_if($lesson->teacher_id !== $teacher->id, 403);
            // Load relationships before deletion for observer
            $lesson->load('studentPackage', 'student');
            $student = $lesson->student;
            $lesson->delete();
            // Observer will handle package and student recalculation
            // Refresh student to get updated package data
            $student->refresh();
            return redirect()->route('teacher.lessons.index')->with('status','Lesson deleted.');
        } catch (\Exception $e) {
            Log::error('Error deleting lesson: ' . $e->getMessage());
            return redirect()->route('teacher.lessons.index')->with('error','Error deleting lesson. Please try again.');
        }
    }
}
