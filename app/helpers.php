<?php

use App\Models\Setting;
use App\Models\Tenant;

if (!function_exists('currentTenant')) {
    function currentTenant(): ?Tenant
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, ?int $tenantId = null, $default = null)
    {
        if (is_null($tenantId) && currentTenant()) {
            $tenantId = currentTenant()->id;
        }

        // Try tenant-specific first, then fall back to global
        $setting = Setting::withoutGlobalScopes()
            ->where('key', $key)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!$tenantId, fn($q) => $q->whereNull('tenant_id'))
            ->first();

        if (!$setting && $tenantId) {
            // Fall back to global setting
            $setting = Setting::withoutGlobalScopes()
                ->where('key', $key)
                ->whereNull('tenant_id')
                ->first();
        }

        return $setting ? $setting->value : $default;
    }
}
