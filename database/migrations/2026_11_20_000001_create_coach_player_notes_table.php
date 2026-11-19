<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private notes a coach keeps about a player. Never shown to players or parents.
        Schema::create('coach_player_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('athlete_name')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['coach_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_player_notes');
    }
};
