<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->string('teacher_timezone', 64)->nullable()->after('timezone');
            $table->time('student_time_from')->nullable()->after('end_time');
            $table->time('student_time_to')->nullable()->after('student_time_from');
            $table->integer('time_difference_hours')->nullable()->after('student_time_to');
            $table->boolean('use_manual_time_diff')->default(false)->after('time_difference_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_timezone',
                'student_time_from',
                'student_time_to',
                'time_difference_hours',
                'use_manual_time_diff',
            ]);
        });
    }
};
