<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_request_coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_request_id')->constrained('session_requests')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['session_request_id', 'coach_id']);
        });

        Schema::table('coaches', function (Blueprint $table) {
            $table->json('sports')->nullable()->after('sport');
            $table->json('specialties')->nullable()->after('specialty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_request_coaches');

        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['sports', 'specialties']);
        });
    }
};
