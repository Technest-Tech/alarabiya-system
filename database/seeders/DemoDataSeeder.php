<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{User,Teacher,Student,Lesson};
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            // Create two teachers
            $t1User = User::factory()->create([
                'role' => 'teacher',
                'hourly_rate' => 50,
                'country_code' => 'AE',
                'whatsapp_number' => '+971500000001',
            ]);
            $t1 = Teacher::create(['user_id' => $t1User->id]);

            $t2User = User::factory()->create([
                'role' => 'teacher',
                'hourly_rate' => 60,
                'country_code' => 'EG',
                'whatsapp_number' => '+201000000002',
            ]);
            $t2 = Teacher::create(['user_id' => $t2User->id]);

            // Students
            $s1 = Student::create([
                'name' => 'Ahmed Ali',
                'country_code' => 'AE',
                'whatsapp_number' => '+971500000010',
                'package_hours_total' => 20,
                'payment_method' => 'bank_transfer',
                'hourly_rate' => 50,
                'assigned_teacher_id' => $t1->id,
            ]);
            $s2 = Student::create([
                'name' => 'Sara Ibrahim',
                'country_code' => 'EG',
                'whatsapp_number' => '+201000000020',
                'package_hours_total' => 30,
                'payment_method' => 'cash',
                'hourly_rate' => 60,
                'assigned_teacher_id' => $t2->id,
            ]);

            // Lessons
            Lesson::create([
                'student_id' => $s1->id,
                'teacher_id' => $t1->id,
                'title' => 'Intro',
                'duration_minutes' => 60,
                'date' => now()->subDays(3)->toDateString(),
                'level' => 'good',
            ]);
            Lesson::create([
                'student_id' => $s1->id,
                'teacher_id' => $t1->id,
                'title' => 'Grammar',
                'duration_minutes' => 90,
                'date' => now()->subDays(1)->toDateString(),
                'level' => 'very_good',
            ]);
            Lesson::create([
                'student_id' => $s2->id,
                'teacher_id' => $t2->id,
                'title' => 'Conversation',
                'duration_minutes' => 120,
                'date' => now()->subDays(2)->toDateString(),
                'level' => 'excellent',
            ]);

            $s1->recalculateHoursTaken();
            $s2->recalculateHoursTaken();
    }
}
