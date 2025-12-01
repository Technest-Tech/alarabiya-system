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
        Schema::create('timezone_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('timezone', 64);
            $table->integer('adjustment_hours'); // +1 or -1
            $table->timestamp('applied_at');
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timezone_adjustments');
    }
};
