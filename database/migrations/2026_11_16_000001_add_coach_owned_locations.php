<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coaches can enter their own venue ("Other location"). Those rows belong to the coach,
     * use status "coach" so they stay out of the public park lists, and keep the full street
     * address in "area", which every screen already shows next to the park name.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('coach_id')->nullable()->after('status')->constrained('coaches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coach_id');
        });
    }
};
