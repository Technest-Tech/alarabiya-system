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
        Schema::table('timetables', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('days_of_week');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->date('deactivated_until')->nullable()->after('deactivated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deactivated_at', 'deactivated_until']);
        });
    }
};
