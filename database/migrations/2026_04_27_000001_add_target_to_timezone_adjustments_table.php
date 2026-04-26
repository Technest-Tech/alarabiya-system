<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timezone_adjustments', function (Blueprint $table) {
            // 'student' = adjusts student_time_from/student_time_to
            // 'teacher' = adjusts start_time/end_time/day_times and regenerates events
            $table->string('target', 10)->default('student')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('timezone_adjustments', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};
