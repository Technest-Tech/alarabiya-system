<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\Student;
use App\Models\StudentPackage;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PackageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonDeletionBillingTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): Teacher
    {
        $user = User::factory()->create(['role' => 'teacher', 'hourly_rate' => 100]);

        return Teacher::create(['user_id' => $user->id, 'currency' => 'EGP']);
    }

    private function makeStudent(Teacher $teacher): Student
    {
        return Student::create([
            'name' => 'Test Student',
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
    }

    private function makeLesson(Student $student, Teacher $teacher, int $minutes, string $date): Lesson
    {
        $lesson = Lesson::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'duration_minutes' => $minutes,
            'date' => $date,
            'status' => 'attended',
            'is_trial' => false,
        ]);

        // SQLite stores DATE columns with a time part, so look the month's
        // package up by date first; MySQL in production matches either way.
        $month = Carbon::parse($date)->startOfMonth();
        $package = StudentPackage::where('student_id', $student->id)
            ->whereDate('month', $month)
            ->first()
            ?? app(PackageService::class)->getOrCreateMonthlyPackage($student->fresh(), $month);
        app(PackageService::class)->assignLessonToPackage($lesson, $package);

        return $lesson->fresh();
    }

    public function test_deleting_a_lesson_removes_its_billing_item_and_updates_the_total(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        // The real-world case: a lesson entered as 6h by mistake, then deleted
        // and re-entered as 30 minutes.
        $wrong = $this->makeLesson($student, $teacher, 360, '2026-09-13');
        $this->makeLesson($student, $teacher, 30, '2026-09-13');

        $this->actingAs($teacher->user)
            ->delete(route('teacher.lessons.destroy', $wrong))
            ->assertRedirect();

        $this->assertDatabaseMissing('lessons', ['id' => $wrong->id]);
        $this->assertSame(
            0,
            BillingItem::whereNull('lesson_id')->count(),
            'Deleting a lesson must not leave an orphaned billing item behind.'
        );

        $billing = Billing::where('student_id', $student->id)->sole();
        $this->assertSame(1, $billing->items()->count());
        $this->assertEquals(11.00, (float) $billing->total_amount);
    }

    public function test_deleting_the_only_lesson_of_a_month_removes_the_empty_billing(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);
        $lesson = $this->makeLesson($student, $teacher, 30, '2026-09-13');

        $lesson->delete();

        $this->assertSame(0, BillingItem::count());
        $this->assertSame(0, Billing::where('student_id', $student->id)->count());
    }

    public function test_lesson_create_update_and_delete_are_recorded_with_the_acting_user(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        $this->actingAs($teacher->user);

        $lesson = $this->makeLesson($student, $teacher, 360, '2026-09-13');
        $lesson->update(['duration_minutes' => 30]);
        $lesson->delete();

        $activities = LessonActivity::where('lesson_id', $lesson->id)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $activities->pluck('action')->all());
        $this->assertTrue($activities->every(fn ($a) => $a->user_id === $teacher->user->id));
        $this->assertSame($teacher->user->name, $activities->first()->user_name);
        $this->assertSame('teacher', $activities->first()->user_role);
        $this->assertSame(360, $activities[0]->changes['duration_minutes']);
        $this->assertSame([360, 30], $activities[1]->changes['duration_minutes']);
        $this->assertSame(30, $activities[2]->changes['duration_minutes']);
    }

    public function test_package_bookkeeping_updates_are_not_recorded_as_lesson_edits(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);

        $lesson = $this->makeLesson($student, $teacher, 30, '2026-09-13');
        $lesson->update(['package_lesson_number' => 5]);

        $this->assertSame(
            ['created'],
            LessonActivity::where('lesson_id', $lesson->id)->pluck('action')->all()
        );
    }

    public function test_purge_command_removes_orphans_from_unpaid_billings_only(): void
    {
        $teacher = $this->makeTeacher();
        $student = $this->makeStudent($teacher);
        $this->makeLesson($student, $teacher, 30, '2026-09-13');
        $this->makeLesson($student, $teacher, 30, '2026-08-13');

        $september = Billing::where('student_id', $student->id)->whereDate('month', '2026-09-01')->sole();
        $august = Billing::where('student_id', $student->id)->whereDate('month', '2026-08-01')->sole();
        $august->update(['status' => 'paid']);

        // Simulate the leftovers the old bug produced.
        $september->items()->create(['lesson_id' => null, 'description' => 'Lesson - Sep 13, 2026', 'duration_minutes' => 360, 'hourly_rate' => 22, 'amount' => 132]);
        $august->items()->create(['lesson_id' => null, 'description' => 'Lesson - Aug 13, 2026', 'duration_minutes' => 30, 'hourly_rate' => 22, 'amount' => 11]);
        $september->update(['total_amount' => 143]);
        $august->update(['total_amount' => 22]);

        $this->artisan('billing:purge-orphaned-items', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(2, BillingItem::whereNull('lesson_id')->count(), 'Dry run must not change data.');

        $this->artisan('billing:purge-orphaned-items', ['--force' => true])->assertSuccessful();

        $this->assertEquals(11.00, (float) $september->fresh()->total_amount);
        $this->assertSame(1, $september->items()->count());

        // Paid billings are reported, never touched.
        $this->assertEquals(22.00, (float) $august->fresh()->total_amount);
        $this->assertSame(2, $august->items()->count());
    }
}
