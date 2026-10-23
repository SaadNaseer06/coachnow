<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_reports', function (Blueprint $table) {
            // Coach-chosen status per skill: [{"skill":"First touch","status":"strong"}, …]
            $table->json('skill_statuses')->nullable()->after('keywords');
        });
    }

    public function down(): void
    {
        Schema::table('session_reports', function (Blueprint $table) {
            $table->dropColumn('skill_statuses');
        });
    }
};
