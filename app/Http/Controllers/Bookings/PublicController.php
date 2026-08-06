<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingService;
use App\Services\Bookings\AppointmentService;
use App\Services\Bookings\PublicAssetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
        protected PublicAssetService $assets,
    ) {
    }

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
            $this->appointments->book($tenant, $site, $service, $data['starts_at'], [
                'name' => $data['guest_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (ValidationException $e) {
            throw $e;
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
}
