<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_group_sessions', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('status');
            $table->string('age_range', 80)->nullable()->after('is_public');
            $table->decimal('price_per_player', 8, 2)->nullable()->after('age_range');
        });
    }

    public function down(): void
    {
        Schema::table('coach_group_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'age_range', 'price_per_player']);
        });
    }
};
