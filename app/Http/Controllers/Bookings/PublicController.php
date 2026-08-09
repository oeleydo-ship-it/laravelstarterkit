<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Services\Bookings\AppointmentService;
use App\Services\Bookings\BookingPaymentService;
use App\Services\Bookings\PublicAssetService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
        protected PublicAssetService $assets,
        protected BookingPaymentService $payments,
    ) {}

    public function boot(Request $request, string $siteKey)
    {
        $site = $request->attributes->get('booking_site');
        $settings = $site->settings ?? [];

        $payload = 'window.__B='.json_encode([
            'k' => $site->public_key,
            'u' => url('/b/'.$site->public_key),
            'c' => $site->brandColor(),
            'g' => [
                'enabled' => (bool) ($settings['widget_enabled'] ?? true),
                'label' => (string) ($settings['widget_label'] ?? 'Book a time'),
                'position' => (string) ($settings['widget_position'] ?? 'bottom-right'),
                'max_displays' => (int) ($settings['max_displays'] ?? 0),
                'frequency_hours' => (int) ($settings['frequency_hours'] ?? 24),
            ],
        ], JSON_UNESCAPED_SLASHES).';';

        if ($css = $this->assets->stylesheet()) {
            $payload .= '(function(){var s=document.createElement("style");s.textContent='
                .json_encode($css, JSON_UNESCAPED_SLASHES)
                .';document.head.appendChild(s);})();';
        }

        return response(
            $payload."\n".$this->assets->javascript(),
            200,
            [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'public, max-age=120',
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }

    public function show(Request $request)
    {
        $site = $request->attributes->get('booking_site');
        $services = BookingService::withoutGlobalScopes()
            ->where('booking_site_id', $site->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.bookings.public.book', [
            'site' => $site,
            'services' => $services,
            'brand' => $site->brandColor(),
            'booked' => $request->boolean('booked') || session()->has('success'),
        ]);
    }

    public function slots(Request $request)
    {
        $site = $request->attributes->get('booking_site');
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        $service = BookingService::withoutGlobalScopes()
            ->where('booking_site_id', $site->id)
            ->where('active', true)
            ->where('id', $data['service_id'])
            ->firstOrFail();

        $day = Carbon::parse($data['date'], $site->timezone ?: 'UTC');
        $slots = $this->appointments->availableSlots($site, $service, $day);

        return response()->json(['slots' => $slots]);
    }

    public function book(Request $request)
    {
        $site = $request->attributes->get('booking_site');
        $tenant = $request->attributes->get('tenant');

        // Honeypot — only treat as bot if filled; use an obscure name so browsers don't autofill it.
        if (filled($request->input('b_meta_hp'))) {
            return redirect()->to(url('/b/'.$site->public_key));
        }

        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'string', 'max:64'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:190'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service = BookingService::withoutGlobalScopes()
            ->where('booking_site_id', $site->id)
            ->where('active', true)
            ->where('id', $data['service_id'])
            ->firstOrFail();

        try {
            $appointment = $this->appointments->book($tenant, $site, $service, $data['starts_at'], [
                'name' => $data['guest_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($appointment->status === BookingAppointment::STATUS_PENDING_PAYMENT) {
                try {
                    return redirect()->away($this->payments->checkout($appointment));
                } catch (\Throwable $exception) {
                    $appointment->update(['status' => BookingAppointment::STATUS_CANCELLED, 'payment_status' => 'failed', 'payment_expires_at' => null]);

                    return redirect()->to(url('/b/'.$site->public_key))->withInput()->withErrors(['service_id' => 'Payment checkout is unavailable. Please try again later.']);
                }
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (LockTimeoutException) {
            return redirect()
                ->to(url('/b/'.$site->public_key))
                ->withInput($request->except(['b_meta_hp']))
                ->withErrors(['starts_at' => 'Another booking is being processed. Please select the time again.']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return redirect()
                ->to(url('/b/'.$site->public_key))
                ->withInput($request->except(['b_meta_hp']))
                ->withErrors(['starts_at' => $e->getMessage() ?: 'That time is no longer available.']);
        }

        return redirect()
            ->to(url('/b/'.$site->public_key).'?booked=1')
            ->with('success', 'Your appointment is confirmed. We sent a confirmation to your email.');
    }

    public function paymentSuccess(Request $request)
    {
        $site = $request->attributes->get('booking_site');
        $request->validate(['session_id' => ['required', 'string', 'max:255']]);

        try {
            $appointment = $this->payments->confirmSession($request->query('session_id'));
        } catch (\Throwable) {
            $appointment = null;
        }

        abort_unless($appointment && $appointment->booking_site_id === $site->id, 404);

        return redirect()->to($appointment->manageUrl())->with('success', 'Payment received. Your appointment is confirmed.');
    }

    public function retryPayment(Request $request, string $siteKey, string $code)
    {
        $appointment = $this->appointmentForCode($request, $code);
        abort_unless($appointment->status === BookingAppointment::STATUS_PENDING_PAYMENT && $appointment->payment_expires_at?->isFuture(), 422, 'This payment hold has expired.');

        try {
            return redirect()->away($this->payments->checkout($appointment));
        } catch (\Throwable) {
            return back()->withErrors(['payment' => 'Payment checkout is unavailable. Please try again later.']);
        }
    }

    public function manage(Request $request, string $siteKey, string $code)
    {
        $appointment = $this->appointmentForCode($request, $code);

        return view('modules.bookings.public.manage', compact('appointment'));
    }

    public function cancel(Request $request, string $siteKey, string $code)
    {
        $appointment = $this->appointmentForCode($request, $code);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        if (in_array($appointment->status, [BookingAppointment::STATUS_SCHEDULED, BookingAppointment::STATUS_PENDING_PAYMENT], true)) {
            $appointment->update([
                'status' => BookingAppointment::STATUS_CANCELLED,
                'cancellation_reason' => $data['reason'] ?? null,
                'cancelled_at' => now(),
                'payment_status' => $appointment->status === BookingAppointment::STATUS_PENDING_PAYMENT ? 'cancelled' : $appointment->payment_status,
                'payment_expires_at' => null,
            ]);
        }

        return back()->with('success', 'Your appointment has been cancelled.');
    }

    public function calendar(Request $request, string $siteKey, string $code)
    {
        $appointment = $this->appointmentForCode($request, $code);
        $escape = fn (string $value) => str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\\;', '\\n'], $value);
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//'.config('app.name').'//Bookings//EN',
            'BEGIN:VEVENT', 'UID:booking-'.$appointment->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:'.$appointment->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$appointment->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$escape($appointment->service?->name ?? 'Appointment'),
            'DESCRIPTION:'.$escape($appointment->notes ?? ''),
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="appointment.ics"',
        ]);
    }

    public function reschedule(Request $request, string $siteKey, string $code)
    {
        $appointment = $this->appointmentForCode($request, $code);
        $data = $request->validate(['starts_at' => ['required', 'string', 'max:64']]);

        try {
            $this->appointments->reschedule($appointment, $data['starts_at']);
        } catch (LockTimeoutException) {
            return back()->withErrors(['starts_at' => 'Another booking is being processed. Please select the time again.']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return back()->withErrors(['starts_at' => $exception->getMessage()]);
        }

        return back()->with('success', 'Your appointment has been rescheduled.');
    }

    private function appointmentForCode(Request $request, string $code): BookingAppointment
    {
        $site = $request->attributes->get('booking_site');

        return BookingAppointment::withoutGlobalScopes()
            ->with(['service', 'site'])
            ->where('booking_site_id', $site->id)
            ->where('confirmation_code', $code)
            ->firstOrFail();
    }
}
