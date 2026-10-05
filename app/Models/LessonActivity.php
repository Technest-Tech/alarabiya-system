<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LessonActivity extends Model
{
    /**
     * Lesson fields worth auditing. Package bookkeeping columns
     * (package_lesson_number, package_cumulative_hours, ...) are rewritten on
     * every recalculation and would drown out real edits.
     */
    public const TRACKED_FIELDS = [
        'student_id',
        'teacher_id',
        'duration_minutes',
        'date',
        'status',
        'is_trial',
        'is_pending',
    ];

    protected $fillable = [
        'lesson_id', 'action', 'student_id', 'teacher_id', 'lesson_date', 'changes',
        'user_id', 'user_name', 'user_role', 'ip_address', 'user_agent', 'url',
    ];

    protected $casts = [
        'changes' => 'array',
        'lesson_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a lesson event. For 'updated', $changes maps each changed field
     * to [old, new]; otherwise it is a snapshot of the tracked fields.
     *
     * Never throws: an audit failure must not block saving a lesson.
     */
    public static function record(Lesson $lesson, string $action, array $changes): void
    {
        try {
            $user = Auth::user();
            $request = app()->runningInConsole() ? null : request();

            static::create([
                'lesson_id' => $lesson->id,
                'action' => $action,
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'lesson_date' => $lesson->date,
                'changes' => $changes,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? (app()->runningInConsole() ? 'system (console)' : null),
                'user_role' => $user?->role,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : null,
                'url' => $request ? mb_substr($request->method().' '.$request->fullUrl(), 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record lesson activity: '.$e->getMessage(), [
                'lesson_id' => $lesson->id,
                'action' => $action,
            ]);
        }
    }

    /**
     * Snapshot of a lesson's tracked fields, as stored in the database.
     */
    public static function snapshot(Lesson $lesson): array
    {
        $snapshot = [];
        foreach (self::TRACKED_FIELDS as $field) {
            $snapshot[$field] = self::normalize($field, $lesson->getAttributes()[$field] ?? null);
        }

        return $snapshot;
    }

    /**
     * Changed tracked fields of a just-updated lesson, as [old, new] pairs.
     */
    public static function diff(Lesson $lesson): array
    {
        $diff = [];
        foreach (array_keys($lesson->getChanges()) as $field) {
            if (! in_array($field, self::TRACKED_FIELDS, true)) {
                continue;
            }

            $old = self::normalize($field, $lesson->getRawOriginal($field));
            $new = self::normalize($field, $lesson->getAttributes()[$field] ?? null);

            if ($old !== $new) {
                $diff[$field] = [$old, $new];
            }
        }

        return $diff;
    }

    private static function normalize(string $field, mixed $value): mixed
    {
        // Flags default to false in the database, so an unset flag is false.
        if (in_array($field, ['is_trial', 'is_pending'], true)) {
            return (bool) $value;
        }

        if ($value === null) {
            return null;
        }

        return match ($field) {
            'student_id', 'teacher_id', 'duration_minutes' => (int) $value,
            'date' => substr((string) $value, 0, 10),
            default => (string) $value,
        };
    }
}
