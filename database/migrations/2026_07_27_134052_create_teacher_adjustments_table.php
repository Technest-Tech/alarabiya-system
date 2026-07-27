<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rewards (bonuses) and deductions (penalties) applied to a teacher's
     * monthly salary, each with an explicit reason and the month it belongs to.
     */
    public function up(): void
    {
        Schema::create('teacher_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['reward', 'deduction'])->index();
            $table->decimal('amount', 10, 2); // positive number, in the teacher's currency
            $table->text('reason');
            $table->date('month')->index(); // first day of the salary month it applies to
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_adjustments');
    }
};
