<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingAppointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', BookingAppointment::class);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = BookingAppointment::query()->with(['service', 'assignee'])->orderByDesc('starts_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->query('q')).'%';
            $query->where(fn ($q) => $q->where('guest_name', 'like', $term)->orWhere('guest_email', 'like', $term));
        }

        return view('modules.bookings.appointments.index', [
            'appointments' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function show(BookingAppointment $appointment)
    {
        $this->authorize('view', $appointment);

        return view('modules.bookings.appointments.show', [
            'appointment' => $appointment->load(['service', 'site', 'client', 'assignee']),
            'users' => \App\Models\User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BookingAppointment $appointment)
    {
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ]);
        $appointment->update($data);

        return back()->with('success', 'Appointment details updated.');
    }

    public function updateStatus(Request $request, BookingAppointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'status' => ['required', 'in:scheduled,cancelled,completed,no_show'],
        ]);

        $appointment->update([
            'status' => $data['status'],
            'cancelled_at' => $data['status'] === BookingAppointment::STATUS_CANCELLED ? ($appointment->cancelled_at ?? now()) : null,
        ]);

        return back()->with('success', 'Appointment updated.');
    }
}
