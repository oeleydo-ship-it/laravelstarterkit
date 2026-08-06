<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAvailability extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'booking_availability';

    protected $fillable = [
        'tenant_id',
        'booking_site_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(BookingSite::class, 'booking_site_id');
    }
}
