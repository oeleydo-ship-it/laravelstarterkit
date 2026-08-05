<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ClientNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'user_id',
        'body',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
