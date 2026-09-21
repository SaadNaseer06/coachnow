<?php

use App\Models\Coach;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('display_name');
        });

        Coach::query()->orderBy('id')->each(function (Coach $coach) {
            $coach->forceFill([
                'slug' => Coach::uniqueSlugFromName($coach->display_name, $coach->id),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
