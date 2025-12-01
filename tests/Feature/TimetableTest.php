<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableEvent;
use App\Models\User;
use App\Services\TimetableGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_timetable_and_generate_events(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(4);

        $payload = [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Quran Studies',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_of_week' => ['monday', 'wednesday'],
        ];

        $response = $this->actingAs($admin)->post(route('timetables.store'), $payload);

        $response->assertRedirect(route('timetables.index', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]));

        $this->assertDatabaseCount('timetables', 1);
        $this->assertDatabaseCount('timetable_events', 2);

        $event = TimetableEvent::first();
        $this->assertSame('Asia/Riyadh', $event->timezone);
        $this->assertSame('Quran Studies', $event->course_name);
        $this->assertSame('09:00', $event->start_at->setTimezone('Asia/Riyadh')->format('H:i'));
    }

    public function test_admin_updating_timetable_regenerates_events(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);
        $timetable = Timetable::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Arabic Basics',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(2)->toDateString(),
            'days_of_week' => ['monday'],
        ]);

        app(TimetableGenerator::class)->regenerate($timetable);

        $this->assertDatabaseCount('timetable_events', 1);

        $response = $this->actingAs($admin)->put(route('timetables.update', $timetable), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Arabic Basics Advanced',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '10:30',
            'end_time' => '11:30',
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(4)->toDateString(),
            'days_of_week' => ['monday', 'wednesday'],
        ]);

        $response->assertRedirect(route('timetables.index', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ]));

        $this->assertDatabaseCount('timetable_events', 2);

        $event = TimetableEvent::first();
        $this->assertSame('Arabic Basics Advanced', $event->course_name);
        $this->assertSame('10:30', $event->start_at->setTimezone('Asia/Riyadh')->format('H:i'));
        $this->assertSame('11:30', $event->end_at->setTimezone('Asia/Riyadh')->format('H:i'));
    }

    public function test_generator_handles_multiple_weekdays_including_sunday(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::parse('next monday');
        $endDate = $startDate->copy()->addWeek();

        $response = $this->actingAs($admin)->post(route('timetables.store'), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Weekend Prep',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '08:00',
            'end_time' => '09:15',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_of_week' => ['sunday', 'monday'],
        ]);

        $response->assertRedirect();

        $timetable = Timetable::first();

        $payloads = app(TimetableGenerator::class)->buildEventPayloads($timetable);
        $this->assertCount(3, $payloads);
        $this->assertSame(['monday', 'sunday', 'monday'], $payloads->map(fn ($payload) => strtolower(Carbon::parse($payload['start_at'])->setTimezone('Asia/Riyadh')->format('l')))->values()->all());

        $events = TimetableEvent::orderBy('start_at')->get();

        // Expect three sessions: Monday (start date), Sunday, Monday (following week)
        $this->assertCount(3, $events);
        $this->assertSame('sunday', strtolower($events[1]->start_at->setTimezone('Asia/Riyadh')->format('l')));
        $this->assertSame('monday', strtolower($events[2]->start_at->setTimezone('Asia/Riyadh')->format('l')));
    }

    public function test_generator_supports_sessions_crossing_midnight(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::parse('next sunday');

        $response = $this->actingAs($admin)->post(route('timetables.store'), [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Late Night Class',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '23:00',
            'end_time' => '01:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addWeek()->toDateString(),
            'days_of_week' => ['sunday'],
        ]);

        $response->assertRedirect();

        $event = TimetableEvent::first();
        $this->assertSame('23:00', $event->start_at->setTimezone('Asia/Riyadh')->format('H:i'));
        $this->assertSame('01:00', $event->end_at->setTimezone('Asia/Riyadh')->format('H:i'));
        $this->assertSame(
            $startDate->copy()->addDay()->toDateString(),
            $event->end_at->setTimezone('Asia/Riyadh')->toDateString(),
            'End should be on the following day'
        );
    }

    public function test_admin_can_update_single_event_via_api(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);

        $timetable = Timetable::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Speaking Club',
            'timezone' => 'Asia/Dubai',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(2)->toDateString(),
            'days_of_week' => ['monday'],
        ]);

        app(TimetableGenerator::class)->regenerate($timetable);

        $event = TimetableEvent::first();
        $targetDate = $startDate->copy()->addDay();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('timetables.events.update', $event), [
                'date' => $targetDate->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
                'teacher_id' => $teacher->id,
                'course_name' => 'Speaking Club Elite',
            ]);

        $response->assertOk()
            ->assertJsonPath('event.extendedProps.is_override', true)
            ->assertJsonPath('event.extendedProps.course_name', 'Speaking Club Elite');

        $event->refresh();

        $this->assertTrue($event->is_override);
        $this->assertSame('Speaking Club Elite', $event->course_name);
        $this->assertSame('18:00', $event->start_at->setTimezone('Asia/Dubai')->format('H:i'));
        $this->assertSame($targetDate->toDateString(), $event->start_at->setTimezone('Asia/Dubai')->toDateString());
    }

    public function test_admin_can_delete_single_event_via_api(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $timetable = Timetable::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Grammar Lab',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'start_date' => Carbon::now()->next(Carbon::MONDAY)->toDateString(),
            'end_date' => Carbon::now()->next(Carbon::MONDAY)->addDays(2)->toDateString(),
            'days_of_week' => ['monday'],
        ]);

        app(TimetableGenerator::class)->regenerate($timetable);

        $event = TimetableEvent::first();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('timetables.events.destroy', $event));

        $response->assertOk();
        $this->assertDatabaseMissing('timetable_events', ['id' => $event->id]);
    }

    public function test_export_endpoint_returns_pdf(): void
    {
        $admin = $this->createAdmin();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create(['assigned_teacher_id' => $teacher->id]);

        $startDate = Carbon::now()->next(Carbon::MONDAY);

        $timetable = Timetable::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'course_name' => 'Writing Skills',
            'timezone' => 'Asia/Riyadh',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(2)->toDateString(),
            'days_of_week' => ['monday'],
        ]);

        app(TimetableGenerator::class)->regenerate($timetable);

        $response = $this->actingAs($admin)->get(route('timetables.export', [
            'student_id' => $student->id,
            'start' => $startDate->toDateString(),
            'end' => $startDate->copy()->addDays(2)->toDateString(),
        ]));

        $response->assertOk();
        $this->assertTrue(Str::contains(strtolower($response->headers->get('content-type')), 'pdf'));
    }
}

