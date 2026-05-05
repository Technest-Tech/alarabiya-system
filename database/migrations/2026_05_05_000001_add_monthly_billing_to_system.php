<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_packages', function (Blueprint $table) {
            $table->date('month')->nullable()->after('student_id')->index();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->decimal('monthly_hours', 8, 2)->nullable()->after('package_hours_total');
        });

        // Backfill month for existing packages from started_at
        DB::table('student_packages')->orderBy('id')->chunkById(100, function ($packages) {
            foreach ($packages as $pkg) {
                $month = Carbon::parse($pkg->started_at)->startOfMonth()->toDateString();
                DB::table('student_packages')->where('id', $pkg->id)->update(['month' => $month]);
            }
        });

        // Resolve any duplicate (student_id, month) pairs before adding the unique constraint.
        // When two packages share the same student+month, keep the one with the highest id.
        $duplicates = DB::table('student_packages')
            ->select('student_id', 'month')
            ->whereNotNull('month')
            ->groupBy('student_id', 'month')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $ids = DB::table('student_packages')
                ->where('student_id', $dup->student_id)
                ->where('month', $dup->month)
                ->orderByDesc('id')
                ->pluck('id')
                ->toArray();
            array_shift($ids); // keep the latest (first after desc sort)
            if (!empty($ids)) {
                DB::table('student_packages')->whereIn('id', $ids)->update(['month' => null]);
            }
        }

        Schema::table('student_packages', function (Blueprint $table) {
            $table->unique(['student_id', 'month'], 'student_packages_student_month_unique');
        });

        // Backfill monthly_hours for existing students using their most recent package
        DB::table('students')->orderBy('id')->chunkById(100, function ($students) {
            foreach ($students as $student) {
                $pkg = null;
                if ($student->current_package_id) {
                    $pkg = DB::table('student_packages')->where('id', $student->current_package_id)->first();
                }
                if (!$pkg) {
                    $pkg = DB::table('student_packages')
                        ->where('student_id', $student->id)
                        ->orderByDesc('started_at')
                        ->first();
                }
                $monthlyHours = $pkg ? $pkg->package_hours : $student->package_hours_total;
                DB::table('students')->where('id', $student->id)->update(['monthly_hours' => $monthlyHours]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_packages', function (Blueprint $table) {
            $table->dropUnique('student_packages_student_month_unique');
            $table->dropIndex(['month']);
            $table->dropColumn('month');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('monthly_hours');
        });
    }
};
