<x-mail::message>
# Booking confirmed

Hi {{ $appointment->guest_name }},

Your appointment is confirmed.

**{{ $appointment->service?->name }}**  
{{ $appointment->starts_at?->timezone($appointment->site?->timezone ?: 'UTC')->format('D, M j Y · g:i A') }}
({{ $appointment->site?->timezone ?: 'UTC' }})

@if($appointment->notes)
Notes: {{ $appointment->notes }}
@endif

Thanks,<br>
{{ $appointment->site?->name ?: config('app.name') }}
</x-mail::message>
