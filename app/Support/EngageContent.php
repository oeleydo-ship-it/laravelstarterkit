<?php

namespace App\Support;

use Illuminate\Support\Str;

class EngageContent
{
    /**
     * Escape visitor-facing text. Campaigns store plain text; the runtime
     * renders via textContent / escaped HTML only.
     */
    public static function plain(?string $value, int $max = 2000): string
    {
        return Str::limit(trim(strip_tags((string) $value)), $max, '');
    }
}
