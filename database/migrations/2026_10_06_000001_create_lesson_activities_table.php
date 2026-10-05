<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail of who created, edited or deleted each lesson.
     *
     * No foreign keys on purpose: the record must survive the lesson (and the
     * user) being deleted, since "who deleted this?" is the main question.
     */
    public function up(): void
    {
        Schema::create('lesson_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id')->index();
            $table->string('action', 20);
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->date('lesson_date')->nullable();
            $table->json('changes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_role', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_activities');
    }
};
