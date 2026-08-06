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
        $query = BookingAppointment::query()->with('service')->orderByDesc('starts_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('modules.bookings.appointments.index', [
            'appointments' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function updateStatus(Request $request, BookingAppointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'status' => ['required', 'in:scheduled,cancelled,completed'],
        ]);

        $appointment->update(['status' => $data['status']]);

        return back()->with('success', 'Appointment updated.');
    }
}
