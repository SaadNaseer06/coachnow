<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_time_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->default('Personal time');
            $table->date('block_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type', 40)->default('personal');
            $table->timestamps();

            $table->index(['coach_id', 'block_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_time_blocks');
    }
};
