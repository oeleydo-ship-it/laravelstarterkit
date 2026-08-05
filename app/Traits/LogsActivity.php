<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logAction('created', $model);
        });

        static::updated(function ($model) {
            static::logAction('updated', $model);
        });

        static::deleted(function ($model) {
            static::logAction('deleted', $model);
        });
    }

    protected static function logAction(string $action, $model): void
    {
        $tenant = currentTenant();
        $user = auth()->user();

        if (!$tenant || !$user) {
            return;
        }

        ActivityLog::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'meta' => json_encode([
                'changes' => $action === 'updated' ? $model->getChanges() : null,
                'name' => $model->name ?? $model->title ?? null,
            ]),
        ]);
    }
}
