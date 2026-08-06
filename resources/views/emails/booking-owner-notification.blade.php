<x-mail::message>
# New booking received

**{{ $appointment->guest_name }}** booked **{{ $appointment->service?->name }}**.

- **When:** {{ $appointment->starts_at?->timezone($appointment->site?->timezone ?: 'UTC')->format('D, M j Y · g:i A') }} ({{ $appointment->site?->timezone ?: 'UTC' }})
- **Email:** {{ $appointment->guest_email }}
@if($appointment->guest_phone)
- **Phone:** {{ $appointment->guest_phone }}
@endif
@if($appointment->notes)
- **Notes:** {{ $appointment->notes }}
@endif

<x-mail::button :url="url('/bookings/appointments')">
View appointments
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
