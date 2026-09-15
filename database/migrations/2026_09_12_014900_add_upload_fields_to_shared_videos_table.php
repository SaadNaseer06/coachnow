<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_videos', function (Blueprint $table) {
            $table->string('source', 20)->default('url')->after('url');
            $table->string('file_path', 500)->nullable()->after('source');
            $table->string('disk', 40)->nullable()->after('file_path');
            $table->unsignedBigInteger('original_bytes')->nullable()->after('disk');
            $table->unsignedBigInteger('stored_bytes')->nullable()->after('original_bytes');
            $table->boolean('is_compressed')->default(false)->after('stored_bytes');
        });

        DB::statement('ALTER TABLE shared_videos MODIFY url VARCHAR(500) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared_videos MODIFY url VARCHAR(500) NOT NULL');

        Schema::table('shared_videos', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'file_path',
                'disk',
                'original_bytes',
                'stored_bytes',
                'is_compressed',
            ]);
        });
    }
};
