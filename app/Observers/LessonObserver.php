<?php

namespace App\Observers;

use App\Models\Lesson;
use App\Services\Billing\BillingService;
use App\Services\PackageService;
use Illuminate\Support\Facades\Log;

class LessonObserver
{
    public function __construct(
        private BillingService $billingService,
        private PackageService $packageService
    ) {
    }

    public function created(Lesson $lesson): void
    {
        $this->billingService->syncLesson($lesson);
    }

    public function updated(Lesson $lesson): void
    {
        $original = [
            'lesson_id' => $lesson->id,
            'student_id' => $lesson->getOriginal('student_id'),
            'date' => $lesson->getOriginal('date'),
        ];

        $this->billingService->syncLesson($lesson, $original);

        // Recalculate package if lesson was updated and has a package
        // Only recalculate if package_id changed, status changed, duration changed, or pending status changed
        // This avoids unnecessary recalculations when other fields are updated
        $packageIdChanged = $lesson->wasChanged('student_package_id');
        $statusChanged = $lesson->wasChanged('status');
        $durationChanged = $lesson->wasChanged('duration_minutes');
        $pendingChanged = $lesson->wasChanged('is_pending');
        
        if ($lesson->studentPackage && ($packageIdChanged || $statusChanged || $durationChanged || $pendingChanged)) {
            $this->packageService->recalculatePackageLessons($lesson->studentPackage);
        }
    }

    public function deleted(Lesson $lesson): void
    {
        try {
            $this->billingService->removeLesson($lesson);
        } catch (\Exception $e) {
            Log::error('Error removing lesson from billing: ' . $e->getMessage());
        }

        // Recalculate package if lesson had a package
        try {
            if ($lesson->studentPackage) {
                $package = $lesson->studentPackage;
                
                // Recalculate all lessons in package (this will set hours_used correctly)
                $this->packageService->recalculatePackageLessons($package);
                
                // If package was completed, check if it should be active again
                if ($package->status === 'completed' && !$package->isExhausted()) {
                    $package->update([
                        'status' => 'active',
                        'completed_at' => null,
                        'is_active' => true,
                    ]);
                }
            }
            
            // Recalculate student's hours_taken_cached
            if ($lesson->student) {
                $lesson->student->recalculateHoursTaken();
            }
        } catch (\Exception $e) {
            Log::error('Error recalculating package after lesson deletion: ' . $e->getMessage());
        }
    }
}


