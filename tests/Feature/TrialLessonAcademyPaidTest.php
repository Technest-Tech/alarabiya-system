<?php

namespace Tests\Feature;

use App\Models\BillingItem;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PackageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialLessonAcademyPaidTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(float $hourlyRate = 100): Teacher
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'hourly_rate' => $hourlyRate,
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'currency' => 'EGP',
        ]);
    }

    private function makeStudent(Teacher $teacher, float $hourlyRate = 50): Student
    {
        return Student::create([
            'name' => 'Test Student',
            'country_code' => 'EG',
            'whatsapp_number' => '+201234567890',
            'package_hours_total' => 20,
            'monthly_hours' => 20,
            'hours_taken_cached' => 0,
            'status' => 'active',
            'payment_method' => 'cash',
            'hourly_rate' => $hourlyRate,
            'currency' => 'USD',
            'assigned_teacher_id' => $teacher->id,
        ]);
    }

    private function makeLesson(Student $student, Teacher $teacher, string $status, int $minutes, string $date): Lesson
    {
        $lesson = Lesson::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'duration_minutes' => $minutes,
            'date' => $date,
            'status' => $status,
            'is_trial' => $status === 'trial',
        ]);

        $package = app(PackageService::class)->getOrCreateMonthlyPackage(
            $student->fresh(),
            Carbon::parse($date)
        );
        app(PackageService::class)->assignLessonToPackage($lesson, $package);

        return $lesson->fresh();
    }

    public function test_trial_lesson_is_not_billed_to_the_student(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        $trial = $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-06');

        $this->assertSame(
            0,
            BillingItem::where('lesson_id', $trial->id)->count(),
            'A trial lesson must never produce a billing item for the student.'
        );
    }

    public function test_trial_lesson_does_not_consume_package_hours(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        $this->makeLesson($student, $teacher, 'attended', 60, '2026-07-06');
        $package = $student->fresh()->currentPackage;
        $hoursAfterAttended = $package->fresh()->hours_used;

        $this->makeLesson($student, $teacher, 'trial', 90, '2026-07-07');

        $this->assertSame(
            $hoursAfterAttended,
            $package->fresh()->hours_used,
            'A trial lesson must not consume any of the student package hours.'
        );
    }

    public function test_trial_lesson_counts_towards_teacher_salary(): void
    {
        $teacher = $this->makeTeacher(hourlyRate: 100);
        $student = $this->makeStudent($teacher);

        $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-06');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/teacher-salaries?month=2026-07')
            ->assertOk();

        $salary = $teacher->fresh()->salaries()->first()
            ?? \App\Models\TeacherSalary::where('teacher_id', $teacher->id)->first();

        $this->assertNotNull($salary, 'A salary record should be generated for the month.');
        $this->assertSame(60, (int) $salary->total_minutes, 'Trial minutes must be paid to the teacher.');
        $this->assertEquals(100.0, (float) $salary->total_amount, 'Teacher must be paid the full hourly rate for a trial.');
    }

    public function test_editing_a_trial_lesson_keeps_it_marked_as_trial(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);
        $trial = $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-06');

        $this->assertTrue($trial->is_trial, 'Sanity: lesson starts out flagged as a trial.');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put("/admin/lessons/{$trial->id}", [
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'hours' => 1,
                'minutes' => 30,
                'date' => '2026-07-06',
                'status' => 'trial',
            ])->assertRedirect();

        $updated = $trial->fresh();

        $this->assertSame('trial', $updated->status);
        $this->assertTrue(
            (bool) $updated->is_trial,
            'Editing a trial lesson must not silently clear the is_trial flag.'
        );
        $this->assertTrue($updated->isAcademyPaid());
    }

    public function test_admin_lesson_list_marks_trials_as_academy_paid(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);
        $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-06');
        $this->makeLesson($student, $teacher, 'attended', 60, '2026-07-07');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/lessons?month=7&year=2026')
            ->assertOk()
            ->assertSee('Academy paid');
    }

    public function test_teacher_lesson_list_marks_trials_as_paid_by_academy(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher', 'hourly_rate' => 100]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'currency' => 'EGP']);
        $student = $this->makeStudent($teacher);
        $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-06');

        $this->actingAs($teacherUser)
            ->get('/teacher/lessons?month=7&year=2026')
            ->assertOk()
            ->assertSee('Paid by academy');
    }

    public function test_salary_page_shows_the_academy_covered_trial_cost(): void
    {
        $teacher = $this->makeTeacher(hourlyRate: 100);
        $student = $this->makeStudent($teacher);
        $this->makeLesson($student, $teacher, 'trial', 90, '2026-07-06');
        $this->makeLesson($student, $teacher, 'attended', 60, '2026-07-07');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/teacher-salaries?month=2026-07')
            ->assertOk()
            ->assertSee('Trials Covered by Academy')
            // 90 min trial at 100/hr = 150.00 of the 250.00 total
            ->assertSee('150.00');
    }

    public function test_salary_csv_export_includes_the_trial_columns(): void
    {
        $teacher = $this->makeTeacher(hourlyRate: 100);
        $student = $this->makeStudent($teacher);
        $this->makeLesson($student, $teacher, 'trial', 90, '2026-07-06');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/teacher-salaries/export?month=2026-07')
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Trial Lessons', $csv);
        $this->assertStringContainsString('Trial Cost (Academy)', $csv);
        $this->assertStringContainsString('150.00 EGP', $csv);
    }

    public function test_academy_paid_scope_and_helpers_only_match_trials(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        $attended = $this->makeLesson($student, $teacher, 'attended', 60, '2026-07-06');
        $trial = $this->makeLesson($student, $teacher, 'trial', 60, '2026-07-07');

        $this->assertFalse($attended->isAcademyPaid());
        $this->assertTrue($trial->isAcademyPaid());

        $this->assertTrue($attended->isTeacherPayable(), 'Attended lessons are payable.');
        $this->assertTrue($trial->isTeacherPayable(), 'Trial lessons are payable by the academy.');

        $this->assertSame(
            [$trial->id],
            Lesson::academyPaid()->pluck('id')->all(),
            'academyPaid() must match only trial lessons.'
        );
    }
}
