<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            // Null = coach doesn't offer parent-booked group sessions on Book Now.
            $table->decimal('group_rate', 8, 2)->nullable()->after('rate');
            $table->unsignedSmallInteger('max_group_size')->default(6)->after('group_rate');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['group_rate', 'max_group_size']);
        });
    }
};
