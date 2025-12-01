<?php

namespace Database\Factories;

use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timetable>
 */
class TimetableFactory extends Factory
{
    protected $model = Timetable::class;

    public function definition(): array
    {
        $startDate = Carbon::now()->addDays(3)->startOfDay();
        $endDate = $startDate->copy()->addWeek();

        return [
            'student_id' => \App\Models\Student::factory(),
            'teacher_id' => \App\Models\Teacher::factory(),
            'course_name' => fake()->words(2, true),
            'timezone' => fake()->randomElement(array_keys(config('timetables.timezones'))),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_of_week' => ['monday', 'wednesday'],
        ];
    }
}

