<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimetableRequest;
use App\Http\Requests\Admin\UpdateTimetableRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\TimetableGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TimetableController extends Controller
{
    public function __construct(
        private readonly TimetableGenerator $generator
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'student_id' => $request->integer('student_id'),
            'teacher_id' => $request->integer('teacher_id'),
            'starts_from' => $request->date('starts_from'),
            'ends_to' => $request->date('ends_to'),
        ];

        $query = Timetable::with(['student', 'teacher.user'])
            ->latest();

        if ($filters['student_id']) {
            $query->where('student_id', $filters['student_id']);
        }

        if ($filters['teacher_id']) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if ($filters['starts_from']) {
            $query->whereDate('start_date', '>=', $filters['starts_from']);
        }

        if ($filters['ends_to']) {
            $query->whereDate('end_date', '<=', $filters['ends_to']);
        }

        $timetables = $query->paginate(15)->withQueryString();

        $students = Student::orderBy('name')->get();
        $teachers = Teacher::with('user')->get()->sortBy(function (Teacher $teacher) {
            return strtolower(optional($teacher->user)->name ?? '');
        });

        return view('admin.timetables.index', [
            'pageTitle' => 'Students Timetables',
            'timetables' => $timetables,
            'students' => $students,
            'teachers' => $teachers,
            'filters' => $filters,
            'timezoneOptions' => $this->timezoneOptions(),
        ]);
    }

    public function store(StoreTimetableRequest $request): RedirectResponse
    {
        $data = $request->payload();

        $timetable = Timetable::create($data);
        
        // Refresh to ensure all attributes are loaded
        $timetable->refresh();

        // Calculate student times
        $teacherTimezone = $timetable->teacher_timezone ?? ($timetable->timezone ?? config('app.timezone'));
        $studentTimezone = $timetable->timezone;
        $this->calculateAndSaveStudentTimes($timetable, $teacherTimezone, $studentTimezone);

        $this->generator->regenerate($timetable->fresh());

        return redirect()
            ->route('timetables.index', Arr::only($request->all(), ['student_id', 'teacher_id', 'starts_from', 'ends_to']))
            ->with('status', 'Timetable created successfully.');
    }

    public function update(UpdateTimetableRequest $request, Timetable $timetable): RedirectResponse
    {
        $data = $request->payload();

        $timetable->update($data);

        // Calculate student times
        $teacherTimezone = $timetable->teacher_timezone ?? $timetable->timezone ?? config('app.timezone');
        $studentTimezone = $timetable->timezone;
        $this->calculateAndSaveStudentTimes($timetable, $teacherTimezone, $studentTimezone);

        $this->generator->regenerate($timetable->fresh());

        // Preserve only student/teacher filters, not date filters (to ensure updated timetable is visible)
        return redirect()
            ->route('timetables.index', Arr::only($request->all(), ['student_id', 'teacher_id']))
            ->with('status', 'Timetable updated successfully.');
    }

    public function destroy(Request $request, Timetable $timetable): RedirectResponse
    {
        $timetable->delete();

        return redirect()
            ->route('timetables.index', Arr::only($request->all(), ['student_id', 'teacher_id', 'starts_from', 'ends_to']))
            ->with('status', 'Timetable deleted successfully.');
    }

    public function deactivate(Request $request, Timetable $timetable): RedirectResponse
    {
        $validated = $request->validate([
            'deactivated_until' => ['nullable', 'date', 'after:today'],
        ]);

        $timetable->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_until' => $validated['deactivated_until'] ?? null,
        ]);

        // Delete future events
        $timetable->events()
            ->where('start_at', '>', now())
            ->delete();

        return redirect()
            ->route('timetables.index', Arr::only($request->all(), ['student_id', 'teacher_id', 'starts_from', 'ends_to']))
            ->with('status', 'Timetable deactivated successfully.');
    }

    public function reactivate(Request $request, Timetable $timetable): RedirectResponse
    {
        $timetable->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_until' => null,
        ]);

        $this->generator->regenerate($timetable->fresh());

        return redirect()
            ->route('timetables.index', Arr::only($request->all(), ['student_id', 'teacher_id', 'starts_from', 'ends_to']))
            ->with('status', 'Timetable reactivated successfully.');
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'deactivated_until' => ['nullable', 'date', 'after:today'],
        ]);

        $timetables = Timetable::where('student_id', $validated['student_id'])
            ->where('is_active', true)
            ->get();

        foreach ($timetables as $timetable) {
            $timetable->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_until' => $validated['deactivated_until'] ?? null,
            ]);

            // Delete future events
            $timetable->events()
                ->where('start_at', '>', now())
                ->delete();
        }

        return redirect()
            ->route('timetables.index', ['student_id' => $validated['student_id']])
            ->with('status', sprintf('Deactivated %d timetable(s) successfully.', $timetables->count()));
    }

    private function timezoneOptions(): array
    {
        return config('timetables.timezones', []);
    }

    private function calculateAndSaveStudentTimes(Timetable $timetable, string $teacherTimezone, ?string $studentTimezone): void
    {
        // Get total timezone adjustment hours (sum of all adjustments)
        $timezone = $studentTimezone ?? $teacherTimezone;
        $totalAdjustmentHours = $this->generator->getTotalAdjustmentHours($timezone);

        // For Egypt timezone, don't apply adjustment to student times
        // For other timezones, apply total adjustments to student times
        $isEgyptTimezone = $timezone === 'Africa/Cairo';
        $studentAdjustmentHours = $isEgyptTimezone ? 0 : $totalAdjustmentHours;

        $this->generator->calculateStudentTimes($timetable, $teacherTimezone, $studentTimezone, $studentAdjustmentHours);
        $timetable->save();
    }
}

