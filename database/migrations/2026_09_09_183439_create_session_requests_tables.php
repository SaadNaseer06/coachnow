<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_name')->nullable();
            $table->string('location_city')->nullable();
            $table->date('session_date');
            $table->time('session_time')->nullable();
            $table->string('session_type')->nullable();
            $table->string('sport')->nullable();
            $table->string('age_range')->nullable();
            $table->string('price_range')->nullable();
            $table->string('player_level')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('min_players')->nullable();
            $table->unsignedTinyInteger('max_players')->nullable();
            $table->unsignedTinyInteger('looking_for')->nullable();
            $table->timestamp('know_by_at')->nullable();
            $table->decimal('deposit_amount', 8, 2)->default(10);
            $table->string('card_on_file')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('host_coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->text('coach_note')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('session_request_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_request_id')->constrained('session_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('initials', 8)->nullable();
            $table->string('role')->default('joiner');
            $table->boolean('paid')->default(false);
            $table->string('paid_with')->nullable();
            $table->string('card_on_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_request_players');
        Schema::dropIfExists('session_requests');
    }
};
