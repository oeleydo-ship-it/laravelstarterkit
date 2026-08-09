<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_services', function (Blueprint $table) {
            $table->string('location_type', 24)->default('online')->after('description');
            $table->string('location_details')->nullable()->after('location_type');
        });
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->timestamp('rescheduled_at')->nullable()->after('cancelled_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('rescheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_services', fn (Blueprint $table) => $table->dropColumn(['location_type', 'location_details']));
        Schema::table('booking_appointments', fn (Blueprint $table) => $table->dropColumn(['rescheduled_at', 'reminder_sent_at']));
    }
};
