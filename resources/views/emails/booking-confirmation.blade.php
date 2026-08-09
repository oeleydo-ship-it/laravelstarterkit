<x-mail::message>
# Booking confirmed

Hi {{ $appointment->guest_name }},

Your appointment is confirmed.

**{{ $appointment->service?->name }}**  
{{ $appointment->starts_at?->timezone($appointment->site?->timezone ?: 'UTC')->format('D, M j Y · g:i A') }}
({{ $appointment->site?->timezone ?: 'UTC' }})

@if($appointment->service?->location_details)
**Location:** {{ $appointment->service->location_details }}
@endif

@if($appointment->notes)
Notes: {{ $appointment->notes }}
@endif

<x-mail::button :url="$appointment->manageUrl()">
Manage booking
</x-mail::button>

Use the manage page to add the appointment to your calendar or cancel it.

Thanks,<br>
{{ $appointment->site?->name ?: config('app.name') }}
</x-mail::message>
