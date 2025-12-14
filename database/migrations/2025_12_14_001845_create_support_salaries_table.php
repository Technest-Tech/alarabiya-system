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
        Schema::create('support_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_name_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->date('month')->index();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->enum('status', ['pending', 'paid'])->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['name', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_salaries');
    }
};
