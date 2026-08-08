<?php
namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AutoblogDestination extends Model {
    use BelongsToTenant;
    protected $fillable=['tenant_id','name','type','base_url','username','secret','is_active','verified_at','verification_error'];
    protected $hidden=['secret'];
    protected function casts(): array { return ['secret'=>'encrypted','is_active'=>'boolean','verified_at'=>'datetime']; }
    public function posts(){ return $this->hasMany(AutoblogPost::class,'destination_id'); }
}
