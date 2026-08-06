<?php
namespace App\Http\Requests\Forms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class FormRequest extends FormRequest {
 public function authorize(): bool { return true; }
 public function rules(): array { return ['name'=>['required','string','max:120'],'type'=>['required',Rule::in(['lead','survey','nps','quiz'])],'status'=>['required',Rule::in(['draft','live','paused'])],'fields'=>['required','array','min:1'],'fields.*.key'=>['required','alpha_dash','max:64'],'fields.*.label'=>['required','string','max:160'],'fields.*.type'=>['required',Rule::in(['text','email','textarea','select','rating','nps'])],'fields.*.required'=>['nullable','boolean'],'fields.*.options'=>['nullable','array'],'thank_you'=>['nullable','string','max:2000']]; }
 public function formPayload(): array { $data=$this->validated(); $data['fields']=array_values(array_map(fn($f)=>['key'=>$f['key'],'label'=>$f['label'],'type'=>$f['type'],'required'=>(bool)($f['required']??false),'options'=>array_values(array_filter($f['options']??[]))],$data['fields'])); $data['settings']=$this->input('settings',[]); return $data; }
}
