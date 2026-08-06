<?php
namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class FormSite extends Model {
    use BelongsToTenant;
    protected $fillable=['tenant_id','public_key','name','allowed_origins','settings'];
    protected function casts(): array { return ['allowed_origins'=>'array','settings'=>'array']; }
    public static function generatePublicKey(): string { return Str::random(40); }
    public function brandColor(): string { return $this->settings['brand_color'] ?? '#2563eb'; }
    public function allowsOrigin(?string $origin): bool { return empty($this->allowed_origins) || ($origin && in_array(rtrim($origin, '/'), array_map(fn($v)=>rtrim($v,'/'), $this->allowed_origins), true)); }
    public function forms(): HasMany { return $this->hasMany(Form::class); }
}
