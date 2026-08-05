<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $stripeSubscription = $payload['data']['object'];
        $stripePriceId = $stripeSubscription['items']['data'][0]['price']['id'] ?? null;

        if ($stripePriceId) {
            // Find plan by stripe price ID
            $plan = Plan::where('stripe_price_id_monthly', $stripePriceId)
                ->orWhere('stripe_price_id_yearly', $stripePriceId)
                ->first();

            if ($plan) {
                // Update tenant plan
                $user = $this->getUserByStripeId($stripeSubscription['customer']);
                if ($user && $user->tenant) {
                    $user->tenant->update(['plan_id' => $plan->id]);
                }
            }
        }
    }

    /**
     * Handle customer subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeCustomerId = $payload['data']['object']['customer'];
        $user = $this->getUserByStripeId($stripeCustomerId);

        if ($user && $user->tenant) {
            $freePlan = Plan::where('key', 'free')->first();
            $user->tenant->update(['plan_id' => $freePlan?->id]);
        }
    }

    protected function getUserByStripeId($stripeId)
    {
        return \App\Models\User::where('stripe_id', $stripeId)->first();
    }
}
