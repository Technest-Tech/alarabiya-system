<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLessonsPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacherWithLessons(int $count, string $month): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'currency' => 'EGP']);

        $student = Student::create([
            'name' => 'Student of '.$user->name,
            'country_code' => 'AE',
            'whatsapp_number' => '+971500000000',
            'package_hours_total' => 20,
            'monthly_hours' => 20,
            'hours_taken_cached' => 0,
            'status' => 'active',
            'payment_method' => 'cash',
            'hourly_rate' => 22,
            'currency' => 'AED',
            'assigned_teacher_id' => $teacher->id,
        ]);

        Lesson::withoutEvents(function () use ($count, $month, $student, $teacher) {
            for ($i = 0; $i < $count; $i++) {
                Lesson::create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'duration_minutes' => 30,
                    'date' => $month.'-'.str_pad((string) ($i % 28 + 1), 2, '0', STR_PAD_LEFT),
                    'status' => 'attended',
                    'is_trial' => false,
                ]);
            }
        });

        return $teacher;
    }

    public function test_next_page_keeps_the_teacher_and_month_filters(): void
    {
        $filtered = $this->makeTeacherWithLessons(35, '2026-09');
        $this->makeTeacherWithLessons(10, '2026-09');

        $admin = User::factory()->create(['role' => 'admin']);

        $page1 = $this->actingAs($admin)->get(route('lessons.index', [
            'teacher_id' => $filtered->id,
            'month' => 9,
            'year' => 2026,
        ]))->assertOk();

        $nextUrl = $page1->viewData('lessons')->nextPageUrl();
        $this->assertNotNull($nextUrl, 'A teacher with 35 lessons should have a second page.');
        $this->assertStringContainsString('teacher_id='.$filtered->id, $nextUrl);
        $this->assertStringContainsString('month=9', $nextUrl);
        $this->assertStringContainsString('year=2026', $nextUrl);

        // Page 2 must contain only the rest of this teacher's lessons.
        $page2 = $this->actingAs($admin)->get($nextUrl)->assertOk();
        $lessons = $page2->viewData('lessons');

        $this->assertSame(5, $lessons->count());
        $this->assertTrue($lessons->every(fn ($l) => $l->teacher_id === $filtered->id));
    }
}
