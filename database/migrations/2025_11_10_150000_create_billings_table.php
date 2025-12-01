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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('month')->index();
            $table->enum('type', ['automatic', 'manual'])->default('automatic')->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'month']);
            $table->unique(['student_id', 'month', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};


