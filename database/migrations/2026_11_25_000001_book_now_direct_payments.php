<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // paid, refunded, waived (member) or sample. Null = no CoachNow fee on this booking.
            $table->string('fee_status', 20)->nullable()->after('platform_fee_amount');
        });

        // One row per fee checkout, so a paid fee always ends in a booking or a refund,
        // even if the parent's browser closes right after paying.
        Schema::create('booking_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->json('payload');
            $table->unsignedSmallInteger('sessions');
            $table->decimal('fee_per_session', 8, 2);
            $table->unsignedInteger('fee_cents');
            $table->string('stripe_payment_intent_id')->unique();
            $table->string('status', 20)->default('pending');
            $table->json('booking_ids')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_checkouts');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('fee_status');
        });
    }
};
