<?php
namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AutoblogPost extends Model {
    use BelongsToTenant;
    protected $fillable=['tenant_id','destination_id','destination_url','created_by','topic','tone','keywords','title','slug','excerpt','content','status','provider','external_id','external_url','last_error','scheduled_at','attempt_count','published_at'];
    protected function casts(): array { return ['published_at'=>'datetime','scheduled_at'=>'datetime']; }
    public function destination(){ return $this->belongsTo(AutoblogDestination::class,'destination_id'); }
    public function author(){ return $this->belongsTo(User::class,'created_by'); }
    public function getDisplayErrorAttribute(): ?string { return $this->last_error ? \App\Services\Autoblog\ProviderError::friendly($this->last_error) : null; }
}
