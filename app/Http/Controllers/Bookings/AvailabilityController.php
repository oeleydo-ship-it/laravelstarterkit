<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingAvailability;
use App\Models\BookingException;
use App\Models\BookingService;
use App\Services\Bookings\SiteService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->middleware(function ($request, $next) {
            abort_unless(
                $request->user()->hasPrivilege(\App\Support\Privileges::BOOKINGS_MANAGE)
                    || $request->user()->isOwnerOrAdmin(),
                403
            );

            return $next($request);
        });
    }

    public function edit()
    {
        $site = $this->sites->defaultFor(currentTenant());
        $rules = BookingAvailability::query()
            ->where('booking_site_id', $site->id)
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();
        $exceptions = BookingException::query()
            ->where('booking_site_id', $site->id)
            ->orderBy('date')
            ->get();

        return view('modules.bookings.availability', compact('site', 'rules', 'exceptions'));
    }

    public function update(Request $request)
    {
        $site = $this->sites->defaultFor(currentTenant());

        $validated = $request->validate([
            'rules' => ['nullable', 'array'],
            'rules.*.weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        BookingAvailability::query()->where('booking_site_id', $site->id)->delete();

        foreach ($validated['rules'] ?? [] as $rule) {
            if ($rule['start_time'] >= $rule['end_time']) {
                continue;
            }
            BookingAvailability::create([
                'tenant_id' => currentTenant()->id,
                'booking_site_id' => $site->id,
                'weekday' => $rule['weekday'],
                'start_time' => $rule['start_time'],
                'end_time' => $rule['end_time'],
            ]);
        }

        return back()->with('success', 'Availability saved.');
    }

    public function storeException(Request $request)
    {
        $site = $this->sites->defaultFor(currentTenant());
        $data = $request->validate([
            'date' => ['required', 'date'],
            'is_closed' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        BookingException::query()->updateOrCreate(
            [
                'booking_site_id' => $site->id,
                'date' => $data['date'],
            ],
            [
                'tenant_id' => currentTenant()->id,
                'is_closed' => $request->boolean('is_closed', true),
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
            ]
        );

        return back()->with('success', 'Exception saved.');
    }

    public function destroyException(BookingException $exception)
    {
        abort_unless($exception->tenant_id === currentTenant()->id, 404);
        $exception->delete();

        return back()->with('success', 'Exception removed.');
    }
}
