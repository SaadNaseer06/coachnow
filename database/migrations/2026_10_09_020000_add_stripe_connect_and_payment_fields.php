<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique()->after('plan');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_payouts_enabled');
            $table->timestamp('stripe_onboarded_at')->nullable()->after('stripe_details_submitted');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->unique()->after('phone');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('amount');
            $table->decimal('platform_fee_amount', 8, 2)->nullable()->after('stripe_payment_intent_id');
            $table->decimal('coach_payout_amount', 8, 2)->nullable()->after('platform_fee_amount');
            $table->string('payment_status', 40)->default('unpaid')->after('coach_payout_amount');
        });

        Schema::table('session_request_players', function (Blueprint $table) {
            $table->string('stripe_payment_method_id')->nullable()->after('card_on_file');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_details_submitted',
                'stripe_onboarded_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_payment_intent_id',
                'platform_fee_amount',
                'coach_payout_amount',
                'payment_status',
            ]);
        });

        Schema::table('session_request_players', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_method_id', 'stripe_payment_intent_id']);
        });
    }
};
