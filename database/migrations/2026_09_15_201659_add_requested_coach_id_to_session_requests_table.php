<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_requests', function (Blueprint $table) {
            $table->foreignId('requested_coach_id')
                ->nullable()
                ->after('host_coach_id')
                ->constrained('coaches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_coach_id');
        });
    }
};
