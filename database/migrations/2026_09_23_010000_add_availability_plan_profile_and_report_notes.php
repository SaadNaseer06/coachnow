<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday … 6=Saturday (Carbon)
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['coach_id', 'day_of_week', 'is_active']);
        });

        Schema::table('coaches', function (Blueprint $table) {
            $table->string('plan', 20)->default('standard')->after('status');
            $table->string('languages_spoken', 255)->nullable()->after('bio');
            $table->text('coaching_philosophy')->nullable()->after('languages_spoken');
            $table->json('credentials')->nullable()->after('coaching_philosophy');
            $table->string('approval_status', 20)->default('none')->after('credentials');
            $table->string('background_check_status', 20)->default('none')->after('approval_status');
            $table->timestamp('verified_at')->nullable()->after('background_check_status');
            $table->timestamp('approved_at')->nullable()->after('verified_at');
            $table->timestamp('last_active_at')->nullable()->after('approved_at');
        });

        Schema::table('session_reports', function (Blueprint $table) {
            $table->text('coach_notes_wins')->nullable()->after('keywords');
            $table->text('coach_notes_work_ons')->nullable()->after('coach_notes_wins');
        });
    }

    public function down(): void
    {
        Schema::table('session_reports', function (Blueprint $table) {
            $table->dropColumn(['coach_notes_wins', 'coach_notes_work_ons']);
        });

        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'languages_spoken',
                'coaching_philosophy',
                'credentials',
                'approval_status',
                'background_check_status',
                'verified_at',
                'approved_at',
                'last_active_at',
            ]);
        });

        Schema::dropIfExists('coach_availability_slots');
    }
};
