<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('athlete_name')->nullable();
            $table->string('title');
            $table->string('url', 500);
            $table->string('description')->nullable();
            $table->string('duration_label', 40)->nullable();
            $table->string('skill_tag', 80)->nullable();
            $table->timestamps();

            $table->index(['athlete_id', 'created_at']);
            $table->index(['coach_id', 'athlete_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_videos');
    }
};
