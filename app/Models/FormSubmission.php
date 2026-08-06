<?php
namespace App\Models;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FormSubmission extends Model {
    use BelongsToTenant;
    protected $fillable=['tenant_id','form_id','email','name','answers','page_url','client_id'];
    protected function casts(): array { return ['answers'=>'array']; }
    public function form(): BelongsTo { return $this->belongsTo(Form::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
