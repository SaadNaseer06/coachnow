<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Parent "Family membership" ($10/mo): no $2 fees, session requests, coach reports.
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('subscription_status', 30)->nullable()->after('stripe_subscription_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
            $table->boolean('subscription_cancel_at_period_end')->default(false)->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_subscription_id', 'subscription_status', 'subscription_ends_at', 'subscription_cancel_at_period_end']);
        });
    }
};
