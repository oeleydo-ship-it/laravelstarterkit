<?php
namespace App\Services\Forms;
use App\Models\FormSite; use App\Models\Tenant;
class SiteService {
 public function defaultFor(Tenant $tenant): FormSite { return FormSite::withoutGlobalScopes()->where('tenant_id',$tenant->id)->orderBy('id')->first() ?? FormSite::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'public_key'=>FormSite::generatePublicKey(),'name'=>'Website','allowed_origins'=>[],'settings'=>['brand_color'=>'#2563eb']]); }
 public function rotateKey(FormSite $site): FormSite { $site->update(['public_key'=>FormSite::generatePublicKey()]); return $site->fresh(); }
 public function saveSettings(FormSite $site,array $data): FormSite { $origins=collect(preg_split('/\r\n|\r|\n/',(string)($data['allowed_origins']??'')))->map(fn($v)=>trim($v))->filter()->values()->all(); $settings=$site->settings??[]; if(!empty($data['brand_color']))$settings['brand_color']=$data['brand_color']; $site->update(['name'=>$data['name']??$site->name,'allowed_origins'=>$origins,'settings'=>$settings]); return $site->fresh(); }
 public function embedSnippet(FormSite $site): string { return '<script src="'.htmlspecialchars(url('/f/'.$site->public_key.'.js'),ENT_QUOTES,'UTF-8').'" async></script>'; }
}
