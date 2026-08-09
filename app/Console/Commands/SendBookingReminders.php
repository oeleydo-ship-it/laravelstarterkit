<?php

namespace App\Console\Commands;

use App\Mail\BookingReminderMail;
use App\Models\BookingAppointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send due booking reminder emails';

    public function handle(): int
    {
        BookingAppointment::withoutGlobalScopes()
            ->with(['site', 'service'])
            ->where('status', BookingAppointment::STATUS_SCHEDULED)
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now(), now()->addDays(7)])
            ->orderBy('id')
            ->chunkById(100, function ($appointments) {
                foreach ($appointments as $appointment) {
                    $settings = $appointment->site?->settings ?? [];
                    $hours = max(1, min(168, (int) ($settings['reminder_hours'] ?? 24)));
                    if (! ($settings['reminders_enabled'] ?? false) || now()->lt($appointment->starts_at->copy()->subHours($hours))) {
                        continue;
                    }

                    try {
                        Mail::to($appointment->guest_email)->send(new BookingReminderMail($appointment));
                        $appointment->update(['reminder_sent_at' => now()]);
                    } catch (\Throwable $exception) {
                        Log::warning('Booking reminder failed', ['appointment_id' => $appointment->id, 'error' => $exception->getMessage()]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
