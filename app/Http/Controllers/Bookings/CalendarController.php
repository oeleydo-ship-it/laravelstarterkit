<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingAppointment;
use App\Models\BookingSite;

class CalendarController extends Controller
{
    public function feed(string $token)
    {
        $site = BookingSite::withoutGlobalScopes()->where('calendar_token', $token)->firstOrFail();
        abort_unless($site->tenant?->isModuleEnabled('bookings'), 404);
        $escape = fn (string $value) => str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $value);
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:'.$escape($site->name)];
        $appointments = BookingAppointment::withoutGlobalScopes()->with('service')->where('booking_site_id', $site->id)
            ->where('status', BookingAppointment::STATUS_SCHEDULED)->whereBetween('starts_at', [now()->subMonth(), now()->addYear()])->orderBy('starts_at')->get();
        foreach ($appointments as $appointment) {
            array_push($lines, 'BEGIN:VEVENT', 'UID:booking-'.$appointment->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
                'DTSTAMP:'.$appointment->updated_at->utc()->format('Ymd\THis\Z'), 'DTSTART:'.$appointment->starts_at->utc()->format('Ymd\THis\Z'),
                'DTEND:'.$appointment->ends_at->utc()->format('Ymd\THis\Z'), 'SUMMARY:'.$escape(($appointment->service?->name ?? 'Appointment').' — '.$appointment->guest_name),
                'DESCRIPTION:'.$escape($appointment->notes ?? ''), 'LOCATION:'.$escape($appointment->service?->location_details ?? ''), 'END:VEVENT');
        }
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Cache-Control' => 'private, max-age=300']);
    }
}
