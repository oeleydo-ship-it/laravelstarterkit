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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AppointmentService
{
    public function __construct(protected ModuleLeadSync $sync) {}

    /**
     * @return list<string> ISO8601 slot starts in site timezone
     */
    public function availableSlots(BookingSite $site, BookingService $service, Carbon $day, ?BookingAppointment $ignore = null): array
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
            ->with('service')
            ->where('booking_site_id', $site->id)
            ->where(function ($query) {
                $query->where('status', BookingAppointment::STATUS_SCHEDULED)
                    ->orWhere(function ($pending) {
                        $pending->where('status', BookingAppointment::STATUS_PENDING_PAYMENT)
                            ->where('payment_expires_at', '>', now());
                    });
            })
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->get();

        $slots = [];
        $now = Carbon::now($tz);
        $settings = $site->settings ?? [];
        $earliest = $now->copy()->addHours(max(0, (int) ($settings['minimum_notice_hours'] ?? 1)));
        $latest = $now->copy()->addDays(max(1, (int) ($settings['maximum_advance_days'] ?? 90)))->endOfDay();

        foreach ($windows as [$startTime, $endTime]) {
            $cursor = Carbon::parse($day->toDateString().' '.$startTime, $tz);
            $end = Carbon::parse($day->toDateString().' '.$endTime, $tz);

            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($cursor->gte($earliest) && $cursor->lte($latest) && ! $this->overlaps($busy, $cursor->copy()->utc(), $slotEnd->copy()->addMinutes($buffer)->utc())) {
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
            $blockedUntil = $row->ends_at->copy()->addMinutes((int) ($row->service?->buffer_minutes ?? 0));
            if ($startUtc->lt($blockedUntil) && $endUtc->gt($row->starts_at)) {
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

        $lockKey = "booking:{$site->id}:".$start->format('Y-m-d');
        $appointment = Cache::lock($lockKey, 15)->block(5, function () use ($tenant, $site, $service, $start, $end, $guest) {
            $slots = $this->availableSlots($site, $service, $start->copy());
            $ok = collect($slots)->contains(fn ($iso) => abs(Carbon::parse($iso)->diffInSeconds($start)) < 60);
            abort_unless($ok, 422, 'That time is no longer available. Please pick another slot.');

            $clientId = $this->sync->sync($tenant, $guest['email'] ?? null, $guest['name'] ?? null, 'bookings', 'Booking Guests');

            $paid = $service->requires_payment && $service->price_cents > 0;

            return BookingAppointment::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'booking_site_id' => $site->id,
                'booking_service_id' => $service->id,
                'starts_at' => $start->copy()->utc(),
                'ends_at' => $end->copy()->utc(),
                'guest_name' => $guest['name'],
                'guest_email' => $guest['email'],
                'guest_phone' => $guest['phone'] ?? null,
                'notes' => $guest['notes'] ?? null,
                'status' => $paid ? BookingAppointment::STATUS_PENDING_PAYMENT : BookingAppointment::STATUS_SCHEDULED,
                'payment_status' => $paid ? 'pending' : 'not_required',
                'amount_cents' => $paid ? $service->price_cents : 0,
                'currency' => strtoupper($service->currency ?: 'USD'),
                'payment_expires_at' => $paid ? now()->addMinutes(31) : null,
                'client_id' => $clientId,
                'confirmation_code' => Str::random(48),
            ]);
        });

        $appointment->load('service', 'site');

        if ($appointment->status === BookingAppointment::STATUS_PENDING_PAYMENT) {
            return $appointment;
        }

        $this->sendNotifications($appointment, $tenant);

        return $appointment;
    }

    public function sendNotifications(BookingAppointment $appointment, ?Tenant $tenant = null): void
    {
        $appointment->loadMissing(['service', 'site']);
        $tenant ??= Tenant::withoutGlobalScopes()->find($appointment->tenant_id);

        try {
            Mail::to($appointment->guest_email)->send(new BookingConfirmationMail($appointment));
        } catch (\Throwable $e) {
            Log::warning('Booking guest confirmation mail failed', ['id' => $appointment->id, 'error' => $e->getMessage()]);
        }

        $owners = User::withoutGlobalScopes()
            ->where('tenant_id', $appointment->tenant_id)
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

    }

    public function reschedule(BookingAppointment $appointment, string $startsAt): BookingAppointment
    {
        abort_unless($appointment->status === BookingAppointment::STATUS_SCHEDULED, 422, 'Only scheduled appointments can be rescheduled.');
        $appointment->loadMissing(['site', 'service']);
        $tz = $appointment->site->timezone ?: 'UTC';
        $start = Carbon::parse($startsAt)->timezone($tz);
        $lockKey = "booking:{$appointment->booking_site_id}:".$start->format('Y-m-d');

        return Cache::lock($lockKey, 15)->block(5, function () use ($appointment, $start) {
            $slots = $this->availableSlots($appointment->site, $appointment->service, $start->copy(), $appointment);
            $ok = collect($slots)->contains(fn ($iso) => abs(Carbon::parse($iso)->diffInSeconds($start)) < 60);
            abort_unless($ok, 422, 'That time is no longer available. Please pick another slot.');

            $appointment->update([
                'starts_at' => $start->copy()->utc(),
                'ends_at' => $start->copy()->addMinutes($appointment->service->duration_minutes)->utc(),
                'rescheduled_at' => now(),
                'reminder_sent_at' => null,
            ]);

            return $appointment->fresh(['site', 'service']);
        });
    }
}
