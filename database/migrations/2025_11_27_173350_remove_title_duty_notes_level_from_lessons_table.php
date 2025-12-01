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
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['title', 'duty', 'notes', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('title')->after('teacher_id');
            $table->text('duty')->nullable()->after('date');
            $table->text('notes')->nullable()->after('duty');
            $table->enum('level', ['not_good','good','very_good','excellent'])->default('good')->after('notes');
        });
    }
};
