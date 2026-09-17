<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('athlete_name')->nullable();
            $table->string('keywords', 255)->nullable();
            $table->text('focus');
            $table->text('went_well');
            $table->text('needs_work');
            $table->text('home_plan');
            $table->text('summary')->nullable();
            $table->json('recommended_videos')->nullable();
            $table->boolean('shared_with_player')->default(false);
            $table->timestamp('shared_at')->nullable();
            $table->string('ai_source', 40)->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'athlete_id']);
            $table->index(['athlete_id', 'shared_with_player']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_reports');
    }
};
