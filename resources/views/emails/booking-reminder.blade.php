<x-mail::message>
# Appointment reminder

Hi {{ $appointment->guest_name }},

This is a reminder about your upcoming **{{ $appointment->service?->name }}** appointment.

**When:** {{ $appointment->starts_at->timezone($appointment->site?->timezone ?: 'UTC')->format('D, M j Y · g:i A') }} ({{ $appointment->site?->timezone ?: 'UTC' }})

@if($appointment->service?->location_details)
**Location:** {{ $appointment->service->location_details }}
@endif

<x-mail::button :url="$appointment->manageUrl()">
Manage appointment
</x-mail::button>

Thanks,<br>
{{ $appointment->site?->name ?: config('app.name') }}
</x-mail::message>
