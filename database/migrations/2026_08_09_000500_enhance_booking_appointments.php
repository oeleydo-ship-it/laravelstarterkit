<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->string('confirmation_code', 64)->nullable()->unique()->after('status');
            $table->foreignId('assigned_to')->nullable()->after('confirmation_code')->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable()->after('notes');
            $table->string('cancellation_reason')->nullable()->after('internal_notes');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropUnique(['confirmation_code']);
            $table->dropColumn(['confirmation_code', 'assigned_to', 'internal_notes', 'cancellation_reason', 'cancelled_at']);
        });
    }
};
