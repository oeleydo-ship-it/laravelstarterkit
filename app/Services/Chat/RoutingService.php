<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;

class RoutingService
{
    public const STRATEGY_MANUAL = 'manual';
    public const STRATEGY_ROUND_ROBIN = 'round_robin';
    public const STRATEGY_LEAST_BUSY = 'least_busy';

    public const SETTING_KEY = 'chat_routing_strategy';

    public static function strategies(): array
    {
        return [
            self::STRATEGY_MANUAL => 'Manual — leave new chats unassigned',
            self::STRATEGY_ROUND_ROBIN => 'Round robin — rotate through available agents',
            self::STRATEGY_LEAST_BUSY => 'Least busy — fewest open chats first',
        ];
    }

    public function strategyFor(Tenant $tenant): string
    {
        $strategy = Setting::get(self::SETTING_KEY, $tenant->id, self::STRATEGY_MANUAL);

        return array_key_exists($strategy, self::strategies())
            ? $strategy
            : self::STRATEGY_MANUAL;
    }

    /**
     * Pick the agent a new conversation should go to, or null to leave it in the
     * unassigned queue (manual routing, or nobody is currently online).
     */
    public function pickAgent(Tenant $tenant): ?User
    {
        return match ($this->strategyFor($tenant)) {
            self::STRATEGY_ROUND_ROBIN => $this->roundRobin($tenant),
            self::STRATEGY_LEAST_BUSY => $this->leastBusy($tenant),
            default => null,
        };
    }

    /**
     * Rotate fairly: the available agent who was assigned a conversation least
     * recently goes next. Agents who have never been assigned one come first.
     */
    protected function roundRobin(Tenant $tenant): ?User
    {
        return $this->availableAgents($tenant)
            ->sortBy(fn (User $agent) => $this->lastAssignedAt($agent) ?? '')
            ->first();
    }

    protected function leastBusy(Tenant $tenant): ?User
    {
        $agents = $this->availableAgents($tenant);

        if ($agents->isEmpty()) {
            return null;
        }

        $openCounts = ChatConversation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'open')
            ->whereIn('assigned_to', $agents->pluck('id'))
            ->selectRaw('assigned_to, count(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        // Ties break toward the agent idle longest, so load stays even.
        return $agents
            ->sortBy(fn (User $agent) => [
                (int) ($openCounts[$agent->id] ?? 0),
                $this->lastAssignedAt($agent) ?? '',
            ])
            ->first();
    }

    protected function availableAgents(Tenant $tenant)
    {
        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->availableAgents()
            ->get();
    }

    protected function lastAssignedAt(User $agent): ?string
    {
        return ChatConversation::withoutGlobalScopes()
            ->where('assigned_to', $agent->id)
            ->max('created_at');
    }
}
