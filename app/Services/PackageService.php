<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPackage;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class PackageService
{
    /**
     * Create a new package for a student
     */
    public function createPackage(Student $student, ?int $packageHours = null): StudentPackage
    {
        $packageHours = $packageHours ?? $student->package_hours_total;

        // Deactivate any existing active packages
        StudentPackage::where('student_id', $student->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $package = StudentPackage::create([
            'student_id' => $student->id,
            'package_hours' => $packageHours,
            'hours_used' => 0,
            'started_at' => now(),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Update student's current package
        $student->update(['current_package_id' => $package->id]);

        return $package;
    }

    /**
     * Check if student's current package is exhausted
     */
    public function isPackageExhausted(Student $student): bool
    {
        if (!$student->currentPackage) {
            return true;
        }

        return $student->currentPackage->isExhausted();
    }

    /**
     * Calculate cumulative hours for a lesson within a package
     * Only counts calculated lessons (attended, absent_student) that are not trial
     */
    public function calculateCumulativeHours(StudentPackage $package, Lesson $lesson): float
    {
        // Get all calculated, non-pending, non-trial lessons in this package before this lesson
        $previousLessons = Lesson::where('student_package_id', $package->id)
            ->where('is_pending', false)
            ->where('status', '!=', 'trial') // Exclude trial status
            ->where('is_trial', false) // Also exclude legacy is_trial field
            ->whereIn('status', ['attended', 'absent_student']) // Only calculated statuses
            ->where(function ($query) use ($lesson) {
                $query->where('date', '<', $lesson->date)
                    ->orWhere(function ($q) use ($lesson) {
                        $q->where('date', '=', $lesson->date)
                            ->where('id', '<', $lesson->id);
                    });
            })
            ->sum('duration_minutes');

        // Add current lesson duration only if it's calculated and not trial
        $totalMinutes = $previousLessons;
        if ($lesson->isCalculated() && !$lesson->isTrial()) {
            $totalMinutes += $lesson->duration_minutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Get next lesson number for a package
     */
    public function getNextLessonNumber(StudentPackage $package): int
    {
        $lastLesson = Lesson::where('student_package_id', $package->id)
            ->orderBy('package_lesson_number', 'desc')
            ->first();

        return ($lastLesson?->package_lesson_number ?? 0) + 1;
    }

    /**
     * Assign lesson to package and calculate values
     * Only calculated lessons (attended, absent_student) that are not trial subtract from package hours
     * If lesson exceeds package, it will be split into two lessons
     */
    public function assignLessonToPackage(Lesson $lesson, StudentPackage $package): void
    {
        // Refresh package to get latest hours_used
        $package->refresh();
        
        // Trial lessons never exhaust packages and don't need splitting
        if ($lesson->isTrial() || !$lesson->isCalculated()) {
            $cumulativeHours = $this->calculateCumulativeHours($package, $lesson);
            $lessonNumber = $this->getNextLessonNumber($package);
            
            $lesson->update([
                'student_package_id' => $package->id,
                'package_cumulative_hours' => $cumulativeHours,
                'is_pending' => false,
                'package_lesson_number' => $lessonNumber,
            ]);
            
            $this->recalculatePackageLessons($package);
            return;
        }
        
        // Calculate current hours_used to check if package would be exhausted
        $currentHoursUsed = $package->hours_used;
        $packageMaxMinutes = $package->package_hours * 60;
        $remainingMinutes = max(0, $packageMaxMinutes - $currentHoursUsed);
        
        // Check if lesson exceeds the remaining package hours
        if ($lesson->duration_minutes > $remainingMinutes && $remainingMinutes > 0) {
            // Split the lesson into two parts
            // IMPORTANT: Save original duration before updating
            $originalDurationMinutes = $lesson->duration_minutes;
            
            // Part 1: The portion that fits in the current package (not pending)
            $part1Minutes = $remainingMinutes;
            $cumulativeHours1 = $this->calculateCumulativeHours($package, $lesson);
            $lessonNumber1 = $this->getNextLessonNumber($package);
            
            $lesson->update([
                'student_package_id' => $package->id,
                'duration_minutes' => $part1Minutes,
                'package_cumulative_hours' => $cumulativeHours1,
                'is_pending' => false,
                'package_lesson_number' => $lessonNumber1,
            ]);
            
            // Part 2: The remaining portion (pending)
            // Use original duration, not the updated one
            $part2Minutes = $originalDurationMinutes - $remainingMinutes;
            
            // Ensure we have a valid pending portion
            if ($part2Minutes <= 0) {
                // This shouldn't happen, but if it does, don't create a pending lesson
                $this->recalculatePackageLessons($package);
                $package->refresh();
                if ($package->status === 'active' && ($package->isExhausted() || $package->lessons()->where('is_pending', true)->exists())) {
                    $package->markAsCompleted();
                }
                return;
            }
            
            // The pending lesson's cumulative should show the package is exhausted
            $pendingLesson = Lesson::create([
                'student_id' => $lesson->student_id,
                'teacher_id' => $lesson->teacher_id,
                'duration_minutes' => $part2Minutes,
                'date' => $lesson->date,
                'status' => $lesson->status,
                'is_trial' => $lesson->is_trial,
                'student_package_id' => $package->id, // Still assigned to this package for now
                'is_pending' => true,
                'package_lesson_number' => $lessonNumber1 + 1,
                'package_cumulative_hours' => round($package->package_hours, 2), // Package is exhausted
            ]);
            
            // Recalculate package lessons to set hours_used correctly
            $this->recalculatePackageLessons($package);
            
            // Check if package should be marked as completed
            $package->refresh();
            if ($package->status === 'active' && ($package->isExhausted() || $package->lessons()->where('is_pending', true)->exists())) {
                $package->markAsCompleted();
            }
        } else {
            // Lesson fits entirely in the package
            $cumulativeHours = $this->calculateCumulativeHours($package, $lesson);
            $lessonNumber = $this->getNextLessonNumber($package);
            
            $lesson->update([
                'student_package_id' => $package->id,
                'package_cumulative_hours' => $cumulativeHours,
                'is_pending' => false,
                'package_lesson_number' => $lessonNumber,
            ]);
            
            // Recalculate package lessons to set hours_used correctly
            $this->recalculatePackageLessons($package);
            
            // Check if package should be marked as completed
            $package->refresh();
            if ($package->status === 'active' && ($package->isExhausted() || $package->lessons()->where('is_pending', true)->exists())) {
                $package->markAsCompleted();
            }
        }
    }

    /**
     * Mark package as paid and create new package
     */
    public function renewPackage(StudentPackage $package): StudentPackage
    {
        DB::transaction(function () use ($package) {
            // Mark current package as paid
            $package->markAsPaid();

            // Create new package
            $newPackage = $this->createPackage($package->student, $package->package_hours);

            // Get all pending lessons for this student
            // These are already split lessons, so we can move them directly
            $pendingLessons = Lesson::where('student_id', $package->student_id)
                ->where('is_pending', true)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            $cumulativeMinutes = 0; // Start fresh for new package
            $lessonNumber = 0;

            foreach ($pendingLessons as $lesson) {
                $lessonNumber++;
                
                // Only count non-trial lessons towards cumulative and package hours
                if (!$lesson->isTrial() && $lesson->isCalculated()) {
                    $cumulativeMinutes += $lesson->duration_minutes;
                }

                // Update lesson to new package
                $lesson->update([
                    'student_package_id' => $newPackage->id,
                    'is_pending' => false,
                    'package_cumulative_hours' => round($cumulativeMinutes / 60, 2),
                    'package_lesson_number' => $lessonNumber,
                ]);

                // Only increment package hours for non-trial, calculated lessons
                if (!$lesson->isTrial() && $lesson->isCalculated()) {
                    $newPackage->increment('hours_used', $lesson->duration_minutes);
                }
            }
            
            // Recalculate to ensure everything is correct
            $this->recalculatePackageLessons($newPackage);

            // Check if new package is exhausted
            if ($newPackage->isExhausted()) {
                $newPackage->markAsCompleted();
            }

            // Recalculate student hours
            $package->student->recalculateHoursTaken();
        });

        return StudentPackage::where('student_id', $package->student_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Recalculate cumulative hours for all lessons in a package
     * Only calculated lessons (attended, absent_student) that are not trial count towards package hours_used
     */
    public function recalculatePackageLessons(StudentPackage $package): void
    {
        $lessons = Lesson::where('student_package_id', $package->id)
            ->where('is_pending', false)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $cumulativeMinutes = 0; // Only for calculated, non-trial lessons
        $lessonNumber = 0;

        foreach ($lessons as $lesson) {
            $lessonNumber++;
            
            // Only add to cumulative if lesson is calculated and not trial
            if ($lesson->isCalculated() && !$lesson->isTrial()) {
                $cumulativeMinutes += $lesson->duration_minutes;
            }
            
            // Calculate cumulative hours (only counting calculated, non-trial lessons)
            $calculatedLessonsBefore = Lesson::where('student_package_id', $package->id)
                ->where('is_pending', false)
                ->where('status', '!=', 'trial') // Exclude trial status
                ->where('is_trial', false) // Also exclude legacy is_trial field
                ->whereIn('status', ['attended', 'absent_student'])
                ->where(function ($query) use ($lesson) {
                    $query->where('date', '<', $lesson->date)
                        ->orWhere(function ($q) use ($lesson) {
                            $q->where('date', '=', $lesson->date)
                                ->where('id', '<=', $lesson->id);
                        });
                })
                ->sum('duration_minutes');

            $lesson->update([
                'package_cumulative_hours' => round($calculatedLessonsBefore / 60, 2),
                'package_lesson_number' => $lessonNumber,
            ]);
        }

        // Update package hours_used (only calculated, non-trial lessons)
        $package->update(['hours_used' => $cumulativeMinutes]);
        
        // Refresh package to get updated hours_used
        $package->refresh();
        
        // Check if package has any pending lessons
        $hasPendingLessons = Lesson::where('student_package_id', $package->id)
            ->where('is_pending', true)
            ->exists();
        
        // Check if package should be marked as completed
        // A package is completed if either:
        // 1. It has exhausted its hours (hours_used >= package_hours)
        // 2. It has pending lessons (meaning hours have been exceeded)
        if ($package->status === 'active' && ($package->isExhausted() || $hasPendingLessons)) {
            $package->markAsCompleted();
        }
        
        // If package was completed but is no longer exhausted and has no pending lessons, reactivate it
        if ($package->status === 'completed' && !$package->isExhausted() && !$hasPendingLessons) {
            $package->update([
                'status' => 'active',
                'completed_at' => null,
                'is_active' => true,
            ]);
        }
    }
}

