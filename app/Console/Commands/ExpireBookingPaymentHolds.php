<?php

namespace App\Console\Commands;

use App\Models\BookingAppointment;
use Illuminate\Console\Command;

class ExpireBookingPaymentHolds extends Command
{
    protected $signature = 'bookings:expire-payment-holds';
    protected $description = 'Release unpaid booking slots after checkout expiration';

    public function handle(): int
    {
        BookingAppointment::withoutGlobalScopes()->where('status', BookingAppointment::STATUS_PENDING_PAYMENT)
            ->where('payment_status', 'pending')->where('payment_expires_at', '<=', now())
            ->update(['status' => BookingAppointment::STATUS_CANCELLED, 'payment_status' => 'expired', 'cancelled_at' => now()]);

        return self::SUCCESS;
    }
}
