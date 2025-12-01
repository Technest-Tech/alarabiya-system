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
        Schema::table('timetable_events', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'cancelled', 'rescheduled', 'absent'])->nullable()->after('is_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_events', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
