<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_group_sessions', function (Blueprint $table) {
            // Null on older rows: discovery falls back to the coach's primary sport.
            $table->string('sport', 60)->nullable()->after('session_type');
        });
    }

    public function down(): void
    {
        Schema::table('coach_group_sessions', function (Blueprint $table) {
            $table->dropColumn('sport');
        });
    }
};
