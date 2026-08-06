<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingException extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'booking_site_id',
        'date',
        'is_closed',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(BookingSite::class, 'booking_site_id');
    }
}
