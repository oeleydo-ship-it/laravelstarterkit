<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingService extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'booking_site_id',
        'name',
        'description',
        'duration_minutes',
        'buffer_minutes',
        'color',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(BookingSite::class, 'booking_site_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BookingAppointment::class);
    }
}
