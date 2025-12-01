<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'teacher_id' => \App\Models\Teacher::factory(),
            'title' => fake()->randomElement(['Intro','Grammar','Conversation']),
            'duration_minutes' => fake()->randomElement([60,90,120]),
            'date' => now()->toDateString(),
            'level' => fake()->randomElement(['not_good','good','very_good','excellent']),
            'duty' => null,
        ];
    }
}
