<?php

namespace App\Services\Billing;

use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function syncLesson(Lesson $lesson, ?array $original = null): void
    {
        // Remove transaction wrapper to avoid isolation issues with concurrent requests
        if ($original) {
            $this->removeLessonFromBilling(
                $original['lesson_id'] ?? $lesson->id
            );
        }

        $this->upsertLessonBilling($lesson);
    }

    public function removeLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            $this->removeLessonFromBilling($lesson->id);
        });
    }

    protected function upsertLessonBilling(Lesson $lesson): void
    {
        // Only create billing items for calculated statuses and non-pending lessons
        if (!$lesson->isCalculated() || $lesson->is_pending) {
            // Remove from billing if it exists
            $this->removeLessonFromBilling($lesson->id);
            return;
        }

        /** @var Student $student */
        $student = $lesson->student()->firstOrFail();

        $month = Carbon::parse($lesson->date)->startOfMonth()->toDateString();

        // Use firstOrCreate with proper exception handling for race conditions
        try {
            $billing = Billing::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'month' => $month,
                    'type' => 'automatic',
                ],
                [
                    'currency' => $this->resolveCurrency($student),
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // If firstOrCreate fails due to race condition, just fetch it
            $isUniqueConstraint = $e->getCode() == 23000 || 
                                  str_contains($e->getMessage(), 'UNIQUE constraint') ||
                                  str_contains($e->getMessage(), 'UNIQUE constraint failed');
            
            if ($isUniqueConstraint) {
                // Another process created it, fetch it now
                $billing = Billing::where('student_id', $student->id)
                    ->where('month', $month)
                    ->where('type', 'automatic')
                    ->first();
                
                if (!$billing) {
                    // If we still can't find it, log and skip (will be created on next lesson)
                    Log::warning('Could not retrieve billing after unique constraint violation', [
                        'student_id' => $student->id,
                        'month' => $month,
                        'lesson_id' => $lesson->id,
                    ]);
                    return; // Skip billing for this lesson, it will be handled on next sync
                }
            } else {
                // Not a unique constraint, rethrow
                throw $e;
            }
        }

        $amount = $this->calculateAmount($lesson->duration_minutes, $student->hourly_rate);

        BillingItem::updateOrCreate(
            ['lesson_id' => $lesson->id],
            [
                'billing_id' => $billing->id,
                'description' => 'Lesson - ' . Carbon::parse($lesson->date)->format('M d, Y'),
                'duration_minutes' => $lesson->duration_minutes,
                'hourly_rate' => $student->hourly_rate,
                'amount' => $amount,
            ]
        );

        $this->recalculateBillingTotal($billing);
    }

    protected function removeLessonFromBilling(int $lessonId): void
    {
        $item = BillingItem::where('lesson_id', $lessonId)->first();

        if (! $item) {
            return;
        }

        $billingId = $item->billing_id;
        $item->delete();

        if (! $billingId) {
            return;
        }

        // Fetch billing separately to avoid relationship issues
        $billing = Billing::find($billingId);
        
        if (! $billing) {
            return;
        }

        $this->recalculateBillingTotal($billing);

        if ($billing->items()->count() === 0) {
            $billing->delete();
        }
    }

    protected function recalculateBillingTotal(Billing $billing): void
    {
        $total = $billing->items()->sum('amount');
        $billing->update(['total_amount' => $total]);
    }

    protected function calculateAmount(int $durationMinutes, float $hourlyRate): float
    {
        return round(($durationMinutes / 60) * $hourlyRate, 2);
    }

    protected function resolveCurrency(Student $student): string
    {
        return $student->currency ?? config('app.currency', 'USD');
    }
}


