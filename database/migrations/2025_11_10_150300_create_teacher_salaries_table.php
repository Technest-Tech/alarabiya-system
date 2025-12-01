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
        Schema::create('teacher_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->date('month')->index();
            $table->unsignedInteger('total_minutes')->default(0);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid'])->default('pending')->index();
            $table->timestamps();

            $table->unique(['teacher_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_salaries');
    }
};


