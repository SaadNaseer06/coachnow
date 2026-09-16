<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            $this->addStringColumn('users', 'role', nullable: false, default: 'athlete');
            $this->addStringColumn('users', 'phone');
            $this->addStringColumn('users', 'sport');
        }

        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('area')->nullable();
                $table->decimal('distance_miles', 5, 1)->nullable();
                $table->string('status')->default('live');
                $table->string('image_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coaches')) {
            Schema::create('coaches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->string('display_name');
                $table->string('specialty')->nullable();
                $table->string('sport')->nullable();
                $table->string('experience')->nullable();
                $table->string('ages')->nullable();
                $table->string('status')->default('pending');
                $table->decimal('rate', 8, 2)->nullable();
                $table->decimal('rating', 3, 1)->nullable();
                $table->unsignedInteger('reviews_count')->default(0);
                $table->string('photo_path')->nullable();
                $table->text('bio')->nullable();
                $table->timestamps();
            });
        } else {
            $this->addStringColumn('coaches', 'sport');
            $this->addStringColumn('coaches', 'experience');
            $this->addStringColumn('coaches', 'ages');
        }

        if (! Schema::hasTable('session_requests')) {
            Schema::create('session_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->string('location_name')->nullable();
                $table->string('location_city')->nullable();
                $table->date('session_date');
                $table->time('session_time')->nullable();
                $table->string('session_type')->nullable();
                $table->string('sport')->nullable();
                $table->string('age_range')->nullable();
                $table->string('price_range')->nullable();
                $table->string('player_level')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedTinyInteger('min_players')->nullable();
                $table->unsignedTinyInteger('max_players')->nullable();
                $table->unsignedTinyInteger('looking_for')->nullable();
                $table->timestamp('know_by_at')->nullable();
                $table->decimal('deposit_amount', 8, 2)->default(10);
                $table->string('card_on_file')->nullable();
                $table->string('status')->default('open');
                $table->foreignId('host_coach_id')->nullable()->constrained('coaches')->nullOnDelete();
                $table->foreignId('requested_coach_id')->nullable()->constrained('coaches')->nullOnDelete();
                $table->text('coach_note')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        } else {
            $this->addStringColumn('session_requests', 'sport');
            $this->addForeignId('session_requests', 'requested_coach_id', 'coaches');
        }

        if (! Schema::hasTable('session_request_players')) {
            Schema::create('session_request_players', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_request_id')->constrained('session_requests')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('initials', 8)->nullable();
                $table->string('role')->default('joiner');
                $table->boolean('paid')->default(false);
                $table->string('paid_with')->nullable();
                $table->string('card_on_file')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->foreignId('session_request_id')->nullable()->constrained('session_requests')->nullOnDelete();
                $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
                $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->string('athlete_name')->nullable();
                $table->string('session_type')->nullable();
                $table->date('session_date');
                $table->time('session_time')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->nullable()->default(60);
                $table->decimal('amount', 8, 2)->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        } else {
            $this->addForeignId('bookings', 'session_request_id', 'session_requests');
            if (! Schema::hasColumn('bookings', 'duration_minutes')) {
                Schema::table('bookings', function (Blueprint $table) {
                    $table->unsignedSmallInteger('duration_minutes')->nullable()->default(60);
                });
            }
        }

        if (! Schema::hasTable('shared_videos')) {
            Schema::create('shared_videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
                $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('athlete_name')->nullable();
                $table->string('title');
                $table->string('url', 500)->nullable();
                $table->string('source', 20)->default('url');
                $table->string('file_path', 500)->nullable();
                $table->string('thumbnail_path', 500)->nullable();
                $table->string('disk', 40)->nullable();
                $table->unsignedBigInteger('original_bytes')->nullable();
                $table->unsignedBigInteger('stored_bytes')->nullable();
                $table->boolean('is_compressed')->default(false);
                $table->string('description')->nullable();
                $table->string('duration_label', 40)->nullable();
                $table->string('skill_tag', 80)->nullable();
                $table->timestamps();
            });
        } else {
            $this->addStringColumn('shared_videos', 'source', length: 20, default: 'url');
            $this->addStringColumn('shared_videos', 'file_path', length: 500);
            $this->addStringColumn('shared_videos', 'thumbnail_path', length: 500);
            $this->addStringColumn('shared_videos', 'disk', length: 40);
            if (! Schema::hasColumn('shared_videos', 'original_bytes')) {
                Schema::table('shared_videos', function (Blueprint $table) {
                    $table->unsignedBigInteger('original_bytes')->nullable();
                });
            }
            if (! Schema::hasColumn('shared_videos', 'stored_bytes')) {
                Schema::table('shared_videos', function (Blueprint $table) {
                    $table->unsignedBigInteger('stored_bytes')->nullable();
                });
            }
            if (! Schema::hasColumn('shared_videos', 'is_compressed')) {
                Schema::table('shared_videos', function (Blueprint $table) {
                    $table->boolean('is_compressed')->default(false);
                });
            }
        }

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('topic');
                $table->text('message');
                $table->string('source')->default('contact');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep existing data. This migration only fills gaps.
    }

    private function addStringColumn(
        string $table,
        string $column,
        bool $nullable = true,
        ?string $default = null,
        int $length = 255,
    ): void {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $nullable, $default, $length) {
            $col = $blueprint->string($column, $length);
            if ($nullable) {
                $col->nullable();
            }
            if ($default !== null) {
                $col->default($default);
            }
        });
    }

    private function addForeignId(string $table, string $column, string $foreignTable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable($foreignTable) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $foreignTable) {
            $blueprint->foreignId($column)->nullable()->constrained($foreignTable)->nullOnDelete();
        });
    }
};
