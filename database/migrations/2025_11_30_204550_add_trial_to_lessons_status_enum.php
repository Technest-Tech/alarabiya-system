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
        // Modify the ENUM column to include 'trial'
        DB::statement("ALTER TABLE `lessons` MODIFY COLUMN `status` ENUM('attended', 'absent_student', 'absent_teacher', 'cancelled_student', 'cancelled_teacher', 'trial') DEFAULT 'attended'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'trial' from the ENUM (note: this will fail if any lessons have 'trial' status)
        DB::statement("ALTER TABLE `lessons` MODIFY COLUMN `status` ENUM('attended', 'absent_student', 'absent_teacher', 'cancelled_student', 'cancelled_teacher') DEFAULT 'attended'");
    }
};
