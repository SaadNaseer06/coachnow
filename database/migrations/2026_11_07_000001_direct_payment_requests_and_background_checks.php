<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_requests', function (Blueprint $table) {
            $table->decimal('price_per_player', 8, 2)->nullable()->after('deposit_amount');
            $table->string('payment_note', 500)->nullable()->after('price_per_player');
        });

        Schema::table('session_request_players', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('role');
            $table->timestamp('confirmed_at')->nullable()->after('status');
        });

        // Anyone already on an accepted session keeps their place.
        DB::table('session_request_players')
            ->whereIn('session_request_id', DB::table('session_requests')->where('status', '!=', 'open')->select('id'))
            ->update(['status' => 'confirmed']);

        Schema::table('coaches', function (Blueprint $table) {
            $table->string('background_check_provider', 120)->nullable()->after('background_check_status');
            $table->date('background_check_date')->nullable()->after('background_check_provider');
            $table->string('payment_instructions', 500)->nullable()->after('background_check_date');
        });

        // "Clear" could be set with one admin click and no real check, so nothing here is proof of a check.
        DB::table('coaches')->where('background_check_status', 'clear')->update(['background_check_status' => 'none']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('membership_email_sent_for')->nullable()->after('subscription_cancel_at_period_end');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('membership_email_sent_for');
        });

        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['background_check_provider', 'background_check_date', 'payment_instructions']);
        });

        Schema::table('session_request_players', function (Blueprint $table) {
            $table->dropColumn(['status', 'confirmed_at']);
        });

        Schema::table('session_requests', function (Blueprint $table) {
            $table->dropColumn(['price_per_player', 'payment_note']);
        });
    }
};
