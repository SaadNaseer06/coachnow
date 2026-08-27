<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('athlete')->after('email'); // admin|coach|athlete
            $table->string('phone')->nullable()->after('role');
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('area')->nullable();
            $table->decimal('distance_miles', 5, 1)->nullable();
            $table->string('status')->default('live'); // live|draft
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('specialty')->nullable();
            $table->string('status')->default('pending'); // pending|active|paused
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('athlete_name')->nullable();
            $table->string('session_type')->nullable();
            $table->date('session_date');
            $table->time('session_time')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('status')->default('pending'); // pending|confirmed|cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('coaches');
        Schema::dropIfExists('locations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone']);
        });
    }
};
