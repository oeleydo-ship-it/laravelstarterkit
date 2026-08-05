<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('superadmin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:plans,key',
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'stripe_price_id_monthly' => 'nullable|string|max:255',
            'stripe_price_id_yearly' => 'nullable|string|max:255',
            'max_users' => 'required|integer|min:-1',
            'max_modules' => 'required|integer|min:-1',
            'storage_limit' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        Plan::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'stripe_price_id_monthly' => $validated['stripe_price_id_monthly'],
            'stripe_price_id_yearly' => $validated['stripe_price_id_yearly'],
            'limits' => json_encode([
                'max_users' => (int) $validated['max_users'],
                'max_modules' => (int) $validated['max_modules'],
                'storage_limit' => (int) $validated['storage_limit'],
            ]),
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        return view('superadmin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:plans,key,' . $plan->id,
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'stripe_price_id_monthly' => 'nullable|string|max:255',
            'stripe_price_id_yearly' => 'nullable|string|max:255',
            'max_users' => 'required|integer|min:-1',
            'max_modules' => 'required|integer|min:-1',
            'storage_limit' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan->update([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'stripe_price_id_monthly' => $validated['stripe_price_id_monthly'],
            'stripe_price_id_yearly' => $validated['stripe_price_id_yearly'],
            'limits' => json_encode([
                'max_users' => (int) $validated['max_users'],
                'max_modules' => (int) $validated['max_modules'],
                'storage_limit' => (int) $validated['storage_limit'],
            ]),
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return redirect()->route('superadmin.plans.index')
                ->with('error', 'Cannot delete a plan that has active tenants.');
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
