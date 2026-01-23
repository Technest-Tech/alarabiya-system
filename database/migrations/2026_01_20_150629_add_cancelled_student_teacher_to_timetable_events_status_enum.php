<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `timetable_events` MODIFY COLUMN `status` ENUM('scheduled', 'cancelled', 'cancelled_student', 'cancelled_teacher', 'rescheduled', 'absent', 'attended') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `timetable_events` MODIFY COLUMN `status` ENUM('scheduled', 'cancelled', 'rescheduled', 'absent', 'attended') NULL");
    }
};
