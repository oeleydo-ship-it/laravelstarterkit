<?php
namespace App\Services\Forms;
use App\Models\Form; use App\Models\FormSubmission; use App\Models\Tenant; use App\Services\ModuleLeadSync; use Illuminate\Support\Str;
class SubmissionService {
 public function __construct(protected ModuleLeadSync $leads) {}
 public function capture(Tenant $tenant, Form $form, array $answers, ?string $pageUrl=null): FormSubmission {
  $email=isset($answers['email'])?Str::lower(trim((string)$answers['email'])):null; $name=isset($answers['name'])?trim((string)$answers['name']):null;
  $clientId=$this->leads->sync($tenant,$email,$name,'forms','Form Leads');
  return FormSubmission::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'form_id'=>$form->id,'email'=>$email,'name'=>$name,'answers'=>$answers,'page_url'=>$pageUrl?Str::limit($pageUrl,2000,''):null,'client_id'=>$clientId]);
 }
}
