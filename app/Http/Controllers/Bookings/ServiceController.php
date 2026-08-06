<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\BookingService;
use App\Services\Bookings\SiteService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(protected SiteService $sites)
    {
        $this->authorizeResource(BookingService::class, 'service');
    }

    public function index()
    {
        return view('modules.bookings.services.index', [
            'services' => BookingService::query()->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('modules.bookings.services.form', [
            'service' => new BookingService([
                'duration_minutes' => 30,
                'buffer_minutes' => 0,
                'color' => '#0f766e',
                'active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $site = $this->sites->defaultFor(currentTenant());

        BookingService::create([
            ...$data,
            'tenant_id' => currentTenant()->id,
            'booking_site_id' => $site->id,
        ]);

        return redirect()->route('bookings.services.index')->with('success', 'Service created.');
    }

    public function edit(BookingService $service)
    {
        return view('modules.bookings.services.form', compact('service'));
    }

    public function update(Request $request, BookingService $service)
    {
        $service->update($this->validated($request));

        return redirect()->route('bookings.services.index')->with('success', 'Service saved.');
    }

    public function destroy(BookingService $service)
    {
        $service->delete();

        return redirect()->route('bookings.services.index')->with('success', 'Service deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $data['buffer_minutes'] = (int) ($data['buffer_minutes'] ?? 0);
        $data['active'] = $request->boolean('active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['color'] = $data['color'] ?? '#0f766e';

        return $data;
    }
}
