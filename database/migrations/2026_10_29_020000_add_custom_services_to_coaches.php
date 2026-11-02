<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            // Extra bookable services a coach offers beyond the standard list (e.g. "Goalkeeper Training").
            $table->json('custom_services')->nullable()->after('specialties');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn('custom_services');
        });
    }
};
