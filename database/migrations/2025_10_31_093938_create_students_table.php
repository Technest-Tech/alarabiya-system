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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp_number'); // E.164 preferred
            $table->string('country_code', 2); // ISO code from dropdown
            $table->unsignedInteger('package_hours_total');
            $table->unsignedInteger('hours_taken_cached')->default(0); // minutes
            $table->enum('status', ['active', 'disabled'])->default('active')->index();
            $table->enum('payment_method', ['cash','bank_transfer','credit_card','paypal','other']);
            $table->decimal('hourly_rate', 8, 2);
            $table->foreignId('assigned_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
