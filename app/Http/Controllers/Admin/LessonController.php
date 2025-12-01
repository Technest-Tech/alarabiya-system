<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);
        $studentId = request('student_id');
        $teacherId = request('teacher_id');
        $showPaid = request('show_paid', false);

        // Build query
        $query = Lesson::with(['student.currentPackage','student.teacher.user','teacher.user','studentPackage'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        // Filter out paid packages by default
        if (!$showPaid) {
            $query->whereDoesntHave('studentPackage', function($q) {
                $q->where('status', 'paid');
            });
        }

        // Apply filters
        if ($studentId) {
            $query->where('student_id', $studentId);
        }
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        $lessons = $query->latest('date')->paginate(30);

        // Calculate statistics for the filtered results
        $statsQuery = Lesson::whereYear('date', $year)
            ->whereMonth('date', $month);
        
        // Filter out paid packages by default (same as main query)
        if (!$showPaid) {
            $statsQuery->whereDoesntHave('studentPackage', function($q) {
                $q->where('status', 'paid');
            });
        }
        
        if ($studentId) {
            $statsQuery->where('student_id', $studentId);
        }
        if ($teacherId) {
            $statsQuery->where('teacher_id', $teacherId);
        }

        $totalLessons = $statsQuery->count();
        $totalMinutes = $statsQuery->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 2);
        
        $attendedCount = (clone $statsQuery)->where('status', 'attended')->count();
        $absentStudentCount = (clone $statsQuery)->where('status', 'absent_student')->count();
        $absentTeacherCount = (clone $statsQuery)->where('status', 'absent_teacher')->count();
        $cancelledCount = (clone $statsQuery)->whereIn('status', ['cancelled_student', 'cancelled_teacher'])->count();
        $pendingCount = (clone $statsQuery)->where('is_pending', true)->count();

        // Get students and teachers for filters
        $students = Student::orderBy('name')->get();
        $teachers = Teacher::with('user')->get();

        return view('admin.lessons.index', compact(
            'lessons', 'month', 'year', 'students', 'teachers', 
            'totalLessons', 'totalHours', 'attendedCount', 
            'absentStudentCount', 'absentTeacherCount', 'cancelledCount', 'pendingCount',
            'studentId', 'teacherId', 'showPaid'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::where('status','active')->orderBy('name')->get();
        $teachers = Teacher::with('user')->get();
        return view('admin.lessons.create', compact('students','teachers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required','exists:students,id'],
            'teacher_id' => ['required','exists:teachers,id'],
            'hours' => ['required','integer','min:0','max:8'],
            'minutes' => ['required','integer','min:0','max:59'],
            'date' => ['required','date','before_or_equal:today'],
            'status' => ['required','in:attended,absent_student,absent_teacher,cancelled_student,cancelled_teacher,trial'],
        ]);
        $student = Student::findOrFail($validated['student_id']);
        
        // Ensure student has a current package
        if (!$student->currentPackage) {
            $this->packageService->createPackage($student);
            $student->refresh();
        }

        $durationMinutes = ((int)$validated['hours'] * 60) + (int)$validated['minutes'];
        
        try {
            $lesson = Lesson::create([
                'student_id' => $validated['student_id'],
                'teacher_id' => $validated['teacher_id'],
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
                ? 'Lesson created and marked as pending (package exhausted).' 
                : 'Lesson created.';
            
            return redirect()->route('lessons.index')->with('status', $message);
        } catch (\Exception $e) {
            Log::error('Error creating lesson: ' . $e->getMessage(), [
                'student_id' => $validated['student_id'],
                'teacher_id' => $validated['teacher_id'],
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
        $students = Student::orderBy('name')->get();
        $teachers = Teacher::with('user')->get();
        return view('admin.lessons.edit', compact('lesson','students','teachers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'student_id' => ['required','exists:students,id'],
            'teacher_id' => ['required','exists:teachers,id'],
            'hours' => ['required','integer','min:0','max:8'],
            'minutes' => ['required','integer','min:0','max:59'],
            'date' => ['required','date','before_or_equal:today'],
            'status' => ['required','in:attended,absent_student,absent_teacher,cancelled_student,cancelled_teacher,trial'],
        ]);
        $student = Student::findOrFail($validated['student_id']);
        
        // Ensure student has a current package
        if (!$student->currentPackage) {
            $this->packageService->createPackage($student);
            $student->refresh();
        }

        $durationMinutes = ((int)$validated['hours'] * 60) + (int)$validated['minutes'];
        $lesson->update([
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
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
        
        $lesson->student->recalculateHoursTaken();
        return redirect('/admin/lessons')->with('status','Lesson updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson)
    {
        try {
            // Load relationships before deletion for observer
            $lesson->load('studentPackage', 'student');
            $student = $lesson->student;
            $lesson->delete();
            // Observer will handle package and student recalculation
            // Refresh student to get updated package data
            $student->refresh();
            return redirect()->route('lessons.index')->with('status','Lesson deleted.');
        } catch (\Exception $e) {
            Log::error('Error deleting lesson: ' . $e->getMessage());
            return redirect()->route('lessons.index')->with('error','Error deleting lesson. Please try again.');
        }
    }
}
