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
        Schema::table('support_attendances', function (Blueprint $table) {
            $table->enum('device_type', ['phone', 'pc'])->nullable()->after('to_time');
            $table->foreignId('support_name_id')->nullable()->after('device_type')->constrained('support_names')->nullOnDelete();
            $table->time('to_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_attendances', function (Blueprint $table) {
            $table->dropForeign(['support_name_id']);
            $table->dropColumn(['device_type', 'support_name_id']);
            $table->time('to_time')->nullable(false)->change();
        });
    }
};
