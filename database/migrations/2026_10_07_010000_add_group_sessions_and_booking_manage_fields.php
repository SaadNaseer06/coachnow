<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_type', 160);
            $table->date('session_date');
            $table->time('session_time');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('max_players')->default(4);
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('open'); // open|full|cancelled
            $table->timestamps();

            $table->index(['coach_id', 'session_date']);
        });

        Schema::create('group_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_session_id')->constrained('coach_group_sessions')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('athlete_name', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('pending'); // pending|accepted|declined
            $table->timestamps();

            $table->unique(['group_session_id', 'athlete_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
            $table->unsignedSmallInteger('max_players')->nullable()->after('notes');
            $table->foreignId('group_session_id')->nullable()->after('max_players')
                ->constrained('coach_group_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_session_id');
            $table->dropColumn(['notes', 'max_players']);
        });

        Schema::dropIfExists('group_join_requests');
        Schema::dropIfExists('coach_group_sessions');
    }
};
