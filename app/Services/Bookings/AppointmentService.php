<?php

namespace App\Services\Bookings;

use App\Mail\BookingConfirmationMail;
use App\Mail\BookingOwnerNotificationMail;
use App\Models\BookingAppointment;
use App\Models\BookingAvailability;
use App\Models\BookingException;
use App\Models\BookingService;
use App\Models\BookingSite;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleLeadSync;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentService
{
    public function __construct(protected ModuleLeadSync $sync)
    {
    }

    /**
     * @return list<string> ISO8601 slot starts in site timezone
     */
    public function availableSlots(BookingSite $site, BookingService $service, Carbon $day): array
    {
        $tz = $site->timezone ?: 'UTC';
        $day = $day->copy()->timezone($tz)->startOfDay();
        $weekday = (int) $day->dayOfWeek;

        $exception = BookingException::withoutGlobalScopes()
            ->where('booking_site_id', $site->id)
            ->whereDate('date', $day->toDateString())
            ->first();

        if ($exception?->is_closed) {
            return [];
        }

        $windows = [];
        if ($exception && ! $exception->is_closed && $exception->start_time && $exception->end_time) {
            $windows[] = [$exception->start_time, $exception->end_time];
        } else {
            $rows = BookingAvailability::withoutGlobalScopes()
                ->where('booking_site_id', $site->id)
                ->where('weekday', $weekday)
                ->get();
            foreach ($rows as $row) {
                $windows[] = [$row->start_time, $row->end_time];
            }
        }

        if ($windows === []) {
            return [];
        }

        $duration = max(5, (int) $service->duration_minutes);
        $buffer = max(0, (int) $service->buffer_minutes);
        $step = $duration + $buffer;

        $dayStart = $day->copy()->startOfDay()->utc();
        $dayEnd = $day->copy()->endOfDay()->utc();

        $busy = BookingAppointment::withoutGlobalScopes()
            ->where('booking_site_id', $site->id)
            ->where('status', BookingAppointment::STATUS_SCHEDULED)
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        $now = Carbon::now($tz);

        foreach ($windows as [$startTime, $endTime]) {
            $cursor = Carbon::parse($day->toDateString().' '.$startTime, $tz);
            $end = Carbon::parse($day->toDateString().' '.$endTime, $tz);

            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($cursor->gte($now) && ! $this->overlaps($busy, $cursor->copy()->utc(), $slotEnd->copy()->addMinutes($buffer)->utc())) {
                    $slots[] = $cursor->toIso8601String();
                }
                $cursor->addMinutes($step);
            }
        }

        return $slots;
    }

    protected function overlaps(Collection $busy, Carbon $startUtc, Carbon $endUtc): bool
    {
        foreach ($busy as $row) {
            if ($startUtc->lt($row->ends_at) && $endUtc->gt($row->starts_at)) {
                return true;
            }
        }

        return false;
    }

    public function book(
        Tenant $tenant,
        BookingSite $site,
        BookingService $service,
        string $startsAt,
        array $guest,
    ): BookingAppointment {
        $tz = $site->timezone ?: 'UTC';
        $start = Carbon::parse($startsAt)->timezone($tz);
        $end = $start->copy()->addMinutes((int) $service->duration_minutes);

        $slots = $this->availableSlots($site, $service, $start->copy());
        $ok = collect($slots)->contains(function ($iso) use ($start) {
            return abs(Carbon::parse($iso)->diffInSeconds($start)) < 60;
        });
        abort_unless($ok, 422, 'That time is no longer available. Please pick another slot.');

        $clientId = $this->sync->sync(
            $tenant,
            $guest['email'] ?? null,
            $guest['name'] ?? null,
            'bookings',
            'Booking Guests',
        );

        $appointment = BookingAppointment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'booking_site_id' => $site->id,
            'booking_service_id' => $service->id,
            'starts_at' => $start->copy()->utc(),
            'ends_at' => $end->copy()->utc(),
            'guest_name' => $guest['name'],
            'guest_email' => $guest['email'],
            'guest_phone' => $guest['phone'] ?? null,
            'notes' => $guest['notes'] ?? null,
            'status' => BookingAppointment::STATUS_SCHEDULED,
            'client_id' => $clientId,
        ]);

        $appointment->load('service', 'site');

        try {
            Mail::to($appointment->guest_email)->send(new BookingConfirmationMail($appointment));
        } catch (\Throwable $e) {
            Log::warning('Booking guest confirmation mail failed', ['id' => $appointment->id, 'error' => $e->getMessage()]);
        }

        $owners = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'admin'])
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        foreach ($owners as $email) {
            try {
                Mail::to($email)->send(new BookingOwnerNotificationMail($appointment));
            } catch (\Throwable $e) {
                Log::warning('Booking owner notification mail failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        return $appointment;
    }
}
