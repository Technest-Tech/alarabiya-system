<?php

namespace Database\Factories;

use App\Models\Timetable;
use App\Models\TimetableEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimetableEvent>
 */
class TimetableEventFactory extends Factory
{
    protected $model = TimetableEvent::class;

    public function definition(): array
    {
        /** @var \App\Models\Timetable $timetable */
        $timetable = Timetable::factory()->create();

        $start = Carbon::parse($timetable->start_date . ' ' . $timetable->start_time, $timetable->timezone)->addDays(1);
        $end = $start->copy()->addMinutes(60);

        return [
            'timetable_id' => $timetable->id,
            'student_id' => $timetable->student_id,
            'teacher_id' => $timetable->teacher_id,
            'course_name' => $timetable->course_name,
            'timezone' => $timetable->timezone,
            'start_at' => $start->utc(),
            'end_at' => $end->utc(),
            'is_override' => false,
            'metadata' => null,
        ];
    }
}

