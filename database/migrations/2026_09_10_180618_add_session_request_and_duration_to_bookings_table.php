<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('session_request_id')->nullable()->after('id')->constrained('session_requests')->nullOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->nullable()->default(60)->after('session_time');
            $table->index(['coach_id', 'session_date']);
            $table->index(['athlete_id', 'session_date']);
            $table->index(['session_request_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['session_request_id', 'athlete_id']);
            $table->dropIndex(['coach_id', 'session_date']);
            $table->dropIndex(['athlete_id', 'session_date']);
            $table->dropConstrainedForeignId('session_request_id');
            $table->dropColumn('duration_minutes');
        });
    }
};
