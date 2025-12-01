<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create initial packages for all existing students
        $students = DB::table('students')->get();
        
        foreach ($students as $student) {
            $hoursUsed = (int) ($student->hours_taken_cached ?? 0);
            $packageHours = (int) ($student->package_hours_total ?? 0);
            $isExhausted = $hoursUsed >= ($packageHours * 60);
            
            // Create initial package
            $packageId = DB::table('student_packages')->insertGetId([
                'student_id' => $student->id,
                'package_hours' => $packageHours,
                'hours_used' => $hoursUsed,
                'started_at' => now(),
                'status' => $isExhausted ? 'completed' : 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update student's current_package_id
            DB::table('students')
                ->where('id', $student->id)
                ->update(['current_package_id' => $packageId]);

            // Assign existing lessons to this package and calculate cumulative hours
            $lessons = DB::table('lessons')
                ->where('student_id', $student->id)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            $cumulativeMinutes = 0;
            $lessonNumber = 0;

            foreach ($lessons as $lesson) {
                $cumulativeMinutes += $lesson->duration_minutes;
                $lessonNumber++;
                $isPending = $cumulativeMinutes > ($packageHours * 60);

                DB::table('lessons')
                    ->where('id', $lesson->id)
                    ->update([
                        'student_package_id' => $packageId,
                        'package_cumulative_hours' => round($cumulativeMinutes / 60, 2),
                        'package_lesson_number' => $lessonNumber,
                        'is_pending' => $isPending,
                    ]);
            }

            // Mark package as completed if exhausted
            if ($isExhausted) {
                DB::table('student_packages')
                    ->where('id', $packageId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove package assignments from lessons
        DB::table('lessons')->update([
            'student_package_id' => null,
            'package_cumulative_hours' => null,
            'package_lesson_number' => null,
            'is_pending' => false,
        ]);

        // Remove current_package_id from students
        DB::table('students')->update(['current_package_id' => null]);
    }
};
