<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_sites', function (Blueprint $table) {
            $table->string('calendar_token', 64)->nullable()->unique()->after('public_key');
        });
        Schema::table('booking_services', function (Blueprint $table) {
            $table->unsignedInteger('price_cents')->default(0)->after('buffer_minutes');
            $table->string('currency', 3)->default('USD')->after('price_cents');
            $table->boolean('requires_payment')->default(false)->after('currency');
        });
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->string('payment_status', 24)->default('not_required')->after('status');
            $table->unsignedInteger('amount_cents')->default(0)->after('payment_status');
            $table->string('currency', 3)->default('USD')->after('amount_cents');
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('currency');
            $table->timestamp('payment_expires_at')->nullable()->after('stripe_checkout_session_id');
            $table->timestamp('paid_at')->nullable()->after('payment_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn(['payment_status', 'amount_cents', 'currency', 'stripe_checkout_session_id', 'payment_expires_at', 'paid_at']);
        });
        Schema::table('booking_services', fn (Blueprint $table) => $table->dropColumn(['price_cents', 'currency', 'requires_payment']));
        Schema::table('booking_sites', function (Blueprint $table) {
            $table->dropUnique(['calendar_token']);
            $table->dropColumn('calendar_token');
        });
    }
};
