<?php

namespace App\Services\Bookings;

use App\Models\BookingAppointment;
use RuntimeException;
use Stripe\StripeClient;

class BookingPaymentService
{
    public function __construct(protected AppointmentService $appointments) {}

    public function configured(): bool
    {
        return filled(config('cashier.secret'));
    }

    public function checkout(BookingAppointment $appointment): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Stripe payments are not configured.');
        }
        $appointment->loadMissing(['service', 'site']);
        $stripe = new StripeClient((string) config('cashier.secret'));
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $appointment->guest_email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($appointment->currency),
                    'unit_amount' => $appointment->amount_cents,
                    'product_data' => ['name' => $appointment->service?->name ?? 'Appointment'],
                ],
            ]],
            'metadata' => ['booking_appointment_id' => (string) $appointment->id],
            'success_url' => url('/b/'.$appointment->site->public_key.'/payment/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $appointment->manageUrl(),
            'expires_at' => now()->addMinutes(31)->timestamp,
        ]);
        $appointment->update(['stripe_checkout_session_id' => $session->id]);

        return $session->url;
    }

    public function confirmSession(string $sessionId): ?BookingAppointment
    {
        if (! $this->configured()) {
            return null;
        }
        $session = (new StripeClient((string) config('cashier.secret')))->checkout->sessions->retrieve($sessionId);
        if ($session->payment_status !== 'paid') {
            return null;
        }

        return $this->markPaid($sessionId, (int) ($session->metadata->booking_appointment_id ?? 0));
    }

    public function markPaid(string $sessionId, ?int $appointmentId = null): ?BookingAppointment
    {
        $appointment = BookingAppointment::withoutGlobalScopes()
            ->where(function ($query) use ($sessionId, $appointmentId) {
                $query->where('stripe_checkout_session_id', $sessionId)
                    ->when($appointmentId, fn ($q) => $q->orWhere('id', $appointmentId));
            })->first();
        if (! $appointment || $appointment->payment_status === 'paid') {
            return $appointment;
        }
        $appointment->update([
            'status' => BookingAppointment::STATUS_SCHEDULED,
            'payment_status' => 'paid',
            'payment_expires_at' => null,
            'paid_at' => now(),
            'stripe_checkout_session_id' => $sessionId,
        ]);

        $this->appointments->sendNotifications($appointment->fresh(['service', 'site']));

        return $appointment->fresh(['service', 'site']);
    }
}
