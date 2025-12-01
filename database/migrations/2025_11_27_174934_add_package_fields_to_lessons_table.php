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
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('student_package_id')->nullable()->after('teacher_id')->constrained('student_packages')->nullOnDelete();
            $table->decimal('package_cumulative_hours', 8, 2)->nullable()->after('student_package_id'); // Cumulative hours used in package up to this lesson
            $table->boolean('is_pending')->default(false)->after('package_cumulative_hours'); // True when package exhausted
            $table->unsignedInteger('package_lesson_number')->nullable()->after('is_pending'); // Lesson number within the package
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['student_package_id']);
            $table->dropColumn(['student_package_id', 'package_cumulative_hours', 'is_pending', 'package_lesson_number']);
        });
    }
};
