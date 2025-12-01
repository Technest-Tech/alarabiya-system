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
        Schema::create('timetable_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('course_name');
            $table->string('timezone', 64);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_override')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('start_at');
            $table->index(['student_id', 'teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_events');
    }
};

