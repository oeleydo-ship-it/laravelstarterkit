<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function modules()
    {
        return $this->hasMany(TeamModule::class);
    }

    public function moduleKeys(): array
    {
        return $this->modules()->pluck('module_key')->all();
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->modules()->where('module_key', $moduleKey)->exists();
    }

    public function syncModules(array $moduleKeys): void
    {
        $moduleKeys = array_values(array_unique(array_filter($moduleKeys)));

        $this->modules()
            ->whereNotIn('module_key', $moduleKeys)
            ->delete();

        foreach ($moduleKeys as $key) {
            $this->modules()->firstOrCreate(['module_key' => $key]);
        }
    }
}
