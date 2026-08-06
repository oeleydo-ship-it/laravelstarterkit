<?php

namespace App\Services\SocialProof;

use App\Models\BookingAppointment;
use App\Models\EmailSubscriber;
use App\Models\SocialProofEvent;
use App\Models\SocialProofSite;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FeedService
{
    public function forSite(Tenant $tenant, SocialProofSite $site, int $limit = 40): array
    {
        $settings = $site->resolvedSettings();
        $items = collect();

        if ($settings['include_fake'] || $settings['include_api']) {
            $sources = [];
            if ($settings['include_fake']) {
                $sources[] = SocialProofEvent::SOURCE_FAKE;
            }
            if ($settings['include_api']) {
                $sources[] = SocialProofEvent::SOURCE_API;
            }

            $stored = SocialProofEvent::withoutGlobalScopes()
                ->where('social_proof_site_id', $site->id)
                ->where('is_active', true)
                ->whereIn('source', $sources)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn (SocialProofEvent $event) => $event->toPublicPayload($settings));

            $items = $items->concat($stored);
        }

        if ($settings['include_live_subscribers'] && $tenant->isModuleEnabled('email')) {
            $items = $items->concat($this->liveSubscribers($tenant, $settings, 15));
        }

        if ($settings['include_live_bookings'] && $tenant->isModuleEnabled('bookings')) {
            $items = $items->concat($this->liveBookings($tenant, $settings, 15));
        }

        return $items
            ->sortByDesc(fn ($item) => $item['at'] ?? '')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function liveSubscribers(Tenant $tenant, array $settings, int $limit): Collection
    {
        return EmailSubscriber::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', EmailSubscriber::STATUS_SUBSCRIBED)
            ->orderByDesc('subscribed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (EmailSubscriber $subscriber) use ($settings) {
                $name = trim(($subscriber->first_name ?? '').' '.($subscriber->last_name ?? ''));
                if ($name === '') {
                    $name = Str::before($subscriber->email, '@') ?: 'Someone';
                }

                return [
                    'id' => 's'.$subscriber->id,
                    'n' => $name,
                    'l' => data_get($subscriber->meta, 'location') ?: data_get($subscriber->meta, 'city'),
                    'i' => data_get($subscriber->meta, 'list_name') ?: 'newsletter',
                    'v' => $settings['subscribe_verb'] ?? 'subscribed to',
                    't' => SocialProofEvent::TYPE_SUBSCRIBE,
                    'a' => null,
                    'u' => null,
                    'at' => optional($subscriber->subscribed_at ?? $subscriber->created_at)?->toIso8601String(),
                ];
            });
    }

    protected function liveBookings(Tenant $tenant, array $settings, int $limit): Collection
    {
        return BookingAppointment::withoutGlobalScopes()
            ->with('service')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [BookingAppointment::STATUS_SCHEDULED, BookingAppointment::STATUS_COMPLETED])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (BookingAppointment $appointment) use ($settings) {
                return [
                    'id' => 'b'.$appointment->id,
                    'n' => $appointment->guest_name ?: 'Someone',
                    'l' => null,
                    'i' => $appointment->service?->name ?: 'a booking',
                    'v' => $settings['purchase_verb'] ?? 'booked',
                    't' => SocialProofEvent::TYPE_PURCHASE,
                    'a' => null,
                    'u' => null,
                    'at' => optional($appointment->created_at)?->toIso8601String(),
                ];
            });
    }
}
