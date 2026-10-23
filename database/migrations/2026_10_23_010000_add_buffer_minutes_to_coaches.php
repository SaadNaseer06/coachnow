<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            // Gap kept free after each session before the next one can be booked.
            $table->unsignedSmallInteger('buffer_minutes')->default(0)->after('max_group_size');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn('buffer_minutes');
        });
    }
};
