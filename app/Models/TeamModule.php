<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamModule extends Model
{
    protected $table = 'team_module';

    protected $fillable = [
        'team_id',
        'module_key',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
