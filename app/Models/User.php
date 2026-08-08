<?php

namespace App\Models;

use App\Support\Privileges;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant, Billable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'tenant_id',
        'role',
        'privileges',
        'status',
        'is_superadmin',
        'chat_availability',
        'chat_last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mirrors the column default so a freshly created model reports a real
     * availability instead of null before it has been refreshed from the database.
     */
    protected $attributes = [
        'chat_availability' => 'offline',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->privileges === null && $user->role) {
                $user->privileges = Privileges::defaultsForRole($user->role);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'chat_last_seen_at' => 'datetime',
            'privileges' => 'array',
        ];
    }

    // ─── Relationships ───

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function assignedConversations()
    {
        return $this->hasMany(ChatConversation::class, 'assigned_to');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    // ─── Privileges ───

    public function privilegeList(): array
    {
        if ($this->isOwner() || $this->isSuperAdmin()) {
            return Privileges::keys();
        }

        $stored = $this->privileges;

        if (! is_array($stored) || $stored === []) {
            return Privileges::defaultsForRole($this->role);
        }

        return array_values(array_intersect(Privileges::keys(), $stored));
    }

    public function hasPrivilege(string $privilege): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        return in_array($privilege, $this->privilegeList(), true);
    }

    public function syncPrivileges(array $privileges): void
    {
        $this->update([
            'privileges' => array_values(array_intersect(Privileges::keys(), $privileges)),
        ]);
    }

    public function applyRolePrivilegeDefaults(): void
    {
        $this->update([
            'privileges' => Privileges::defaultsForRole($this->role),
        ]);
    }

    /**
     * Module access: tenant must enable it, then owner/admin always, otherwise
     * privilege or membership in a team linked to that module.
     */
    public function canAccessModule(string $moduleKey): bool
    {
        $tenant = $this->relationLoaded('tenant')
            ? $this->tenant
            : (app()->bound('tenant') ? app('tenant') : null);

        if ($tenant && method_exists($tenant, 'isModuleEnabled') && ! $tenant->isModuleEnabled($moduleKey)) {
            return false;
        }

        if ($this->isOwnerOrAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        if ($this->hasPrivilege("{$moduleKey}.view")
            || $this->hasPrivilege("{$moduleKey}.manage")
            || $this->hasPrivilege("{$moduleKey}.agent")
            || $this->hasPrivilege("{$moduleKey}.access")) {
            return true;
        }

        return $this->teams()
            ->whereHas('modules', fn ($q) => $q->where('module_key', $moduleKey))
            ->exists();
    }

    public function canActAsChatAgent(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isOwnerOrAdmin() || $this->hasPrivilege(Privileges::CHAT_AGENT)) {
            return true;
        }

        return $this->teams()
            ->whereHas('modules', fn ($q) => $q->where('module_key', 'chat'))
            ->exists();
    }

    // ─── Chat Availability ───

    public function isAvailableForChat(): bool
    {
        return $this->canActAsChatAgent() && $this->chat_availability === 'online';
    }

    /**
     * Agents who can currently take a new conversation. Scoped to the active
     * tenant by the model's global scope, so callers get their own agents only.
     */
    public function scopeAvailableAgents($query)
    {
        return $query->chatAgents()->where('chat_availability', 'online');
    }

    public function scopeChatAgents($query)
    {
        return $query->where('status', 'active')
            ->where(function ($outer) {
                $outer->whereIn('role', ['owner', 'admin'])
                    ->orWhereJsonContains('privileges', Privileges::CHAT_AGENT)
                    ->orWhereHas('teams.modules', fn ($q) => $q->where('module_key', 'chat'));
            });
    }

    // ─── Role Helpers ───

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isOwnerOrAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function needsOnboarding(): bool
    {
        return !$this->is_superadmin && is_null($this->tenant_id);
    }

    /**
     * Override BelongsToTenant boot — superadmins bypass tenant scope.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            // NOTE: must use hasUser() and never check()/user() here. This scope runs
            // on User queries, including the one the session guard makes to resolve
            // the logged-in user. check()/user() would ask the guard to resolve that
            // user, re-entering this scope, and recursing until the process dies.
            // hasUser() only reports an already-resolved user and never triggers a load.
            if (auth()->hasUser() && auth()->user()->is_superadmin) {
                return; // Superadmins see everything
            }

            $tenant = app()->bound('tenant') ? app('tenant') : null;

            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }
        });
    }
}
