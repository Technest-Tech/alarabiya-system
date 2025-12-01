<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'country_code' => fake()->randomElement(['AE','EG','SA','US']),
            'whatsapp_number' => '+1'.fake()->numerify('##########'),
            'package_hours_total' => fake()->randomElement([10,20,30]),
            'hours_taken_cached' => 0,
            'status' => 'active',
            'payment_method' => fake()->randomElement(['cash','bank_transfer','credit_card','paypal','other']),
            'hourly_rate' => fake()->randomElement([40,50,60]),
            'assigned_teacher_id' => \App\Models\Teacher::factory(),
        ];
    }
}
