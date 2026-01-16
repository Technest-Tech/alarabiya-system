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
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);
        $studentId = request('student_id');
        
        $query = Lesson::with(['student.currentPackage','studentPackage'])
            ->where('teacher_id', $teacher->id);
        
        // If student is selected, show all lessons from their active/pending packages
        // Otherwise, filter by month/year
        if ($studentId) {
            $student = Student::find($studentId);
            if ($student) {
                // Get all active and completed (but not paid) package IDs for this student
                $packageIds = StudentPackage::where('student_id', $studentId)
                    ->whereIn('status', ['active', 'completed'])
                    ->pluck('id');
                
                $query->where('student_id', $studentId)
                    ->whereIn('student_package_id', $packageIds);
            }
        } else {
            // Apply month/year filter only when no student is selected
            $query->whereYear('date', $year)
                ->whereMonth('date', $month);
        }
        
        $lessons = $query->latest('date')->paginate(20);
        
        // Calculate total minutes with same logic
        $totalMinutesQuery = Lesson::where('teacher_id', $teacher->id);
        
        if ($studentId) {
            $student = Student::find($studentId);
            if ($student) {
                $packageIds = StudentPackage::where('student_id', $studentId)
                    ->whereIn('status', ['active', 'completed'])
                    ->pluck('id');
                
                $totalMinutesQuery->where('student_id', $studentId)
                    ->whereIn('student_package_id', $packageIds);
            }
        } else {
            $totalMinutesQuery->whereYear('date', $year)
                ->whereMonth('date', $month);
        }
        
        $totalMinutes = $totalMinutesQuery->sum('duration_minutes');
        
        // Get all students assigned to this teacher
        $students = Student::where('assigned_teacher_id', $teacher->id)
            ->orderBy('name')
            ->get();
        
        return view('teacher.lessons.index', [
            'lessons' => $lessons,
            'totalMinutes' => $totalMinutes,
            'month' => $month,
            'year' => $year,
            'students' => $students,
            'studentId' => $studentId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teacher = Auth::user()->teacher;
        $students = Student::where('assigned_teacher_id', $teacher->id)->orderBy('name')->get();
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

        // Ensure student has a current package
        if (!$student->currentPackage) {
            $this->packageService->createPackage($student);
            $student->refresh();
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

            // Refresh student to get current package
            $student->refresh();
            
            // Ensure student still has a current package
            if (!$student->currentPackage) {
                $this->packageService->createPackage($student);
                $student->refresh();
            }

            // Assign lesson to package
            $this->packageService->assignLessonToPackage($lesson, $student->currentPackage);
            
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
        $students = Student::where('assigned_teacher_id', $teacher->id)->orderBy('name')->get();
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
        
        // Ensure student has a current package
        if (!$student->currentPackage) {
            $this->packageService->createPackage($student);
            $student->refresh();
        }

        $durationMinutes = ((int)$validated['hours'] * 60) + (int)$validated['minutes'];
        $lesson->update([
            'student_id' => $student->id,
            'duration_minutes' => $durationMinutes,
            'date' => $validated['date'],
            'status' => $validated['status'],
            'is_trial' => $validated['is_trial'] ?? false,
        ]);

        // Reassign lesson to package if package changed
        if ($lesson->student_package_id !== $student->currentPackage->id) {
            $this->packageService->assignLessonToPackage($lesson, $student->currentPackage);
        } else {
            // Recalculate cumulative hours if duration changed
            $this->packageService->recalculatePackageLessons($student->currentPackage);
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
