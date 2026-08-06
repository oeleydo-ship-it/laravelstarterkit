<?php
namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Form extends Model {
    use BelongsToTenant;
    public const STATUS_DRAFT='draft', STATUS_LIVE='live', STATUS_PAUSED='paused';
    protected $fillable=['tenant_id','form_site_id','name','type','status','fields','settings','thank_you'];
    protected function casts(): array { return ['fields'=>'array','settings'=>'array']; }
    public static function types(): array { return ['lead'=>'Lead form','survey'=>'Survey','nps'=>'NPS','quiz'=>'Quiz']; }
    public static function statuses(): array { return [self::STATUS_DRAFT=>'Draft',self::STATUS_LIVE=>'Live',self::STATUS_PAUSED=>'Paused']; }
    public function typeLabel(): string { return self::types()[$this->type] ?? ucfirst($this->type); }
    public function statusLabel(): string { return self::statuses()[$this->status] ?? ucfirst($this->status); }
    public function statusBadgeClass(): string { return match($this->status) { self::STATUS_LIVE=>'bg-success',self::STATUS_PAUSED=>'bg-warning text-dark',default=>'bg-secondary' }; }
    public function toPublicPayload(): array { return ['id'=>$this->id,'n'=>$this->name,'t'=>$this->type,'f'=>$this->fields,'s'=>$this->settings ?? [],'y'=>$this->thank_you]; }
    public function site(): BelongsTo { return $this->belongsTo(FormSite::class,'form_site_id'); }
    public function submissions(): HasMany { return $this->hasMany(FormSubmission::class); }
}
