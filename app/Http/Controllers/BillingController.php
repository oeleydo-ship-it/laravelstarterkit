<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $tenant = currentTenant();
        $currentPlan = $tenant->plan;

        return view('billing.plans', compact('plans', 'currentPlan', 'tenant'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|exists:plans,key',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::where('key', $request->plan)->firstOrFail();

        if ($plan->isFree()) {
            $tenant = currentTenant();
            $tenant->update(['plan_id' => $plan->id]);

            // Cancel any existing subscription
            $user = $request->user();
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancel();
            }

            return redirect()->route('billing.status')->with('success', 'Switched to the Free plan.');
        }

        $priceId = $request->interval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (!$priceId) {
            return redirect()->back()->with('error', 'Stripe price not configured for this plan.');
        }

        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.status') . '?checkout=success',
                'cancel_url' => route('billing.plans') . '?checkout=cancelled',
                'metadata' => [
                    'plan_key' => $plan->key,
                    'tenant_id' => currentTenant()->id,
                ],
            ]);
    }

    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.status'));
    }

    public function status()
    {
        $user = auth()->user();
        $tenant = currentTenant();
        $subscription = $user->subscription('default');
        $currentPlan = $tenant->plan;

        return view('billing.status', compact('user', 'tenant', 'subscription', 'currentPlan'));
    }
}
