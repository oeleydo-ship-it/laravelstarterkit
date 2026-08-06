<?php
namespace App\Http\Controllers\Forms;
use App\Http\Controllers\Controller; use App\Http\Requests\Forms\FormRequest; use App\Models\Form; use App\Services\Forms\SiteService; use App\Support\FormTemplates; use Illuminate\Http\Request;
class FormController extends Controller {
 public function __construct(protected SiteService $sites) { $this->authorizeResource(Form::class,'form'); }
 public function index() { return view('modules.forms.forms.index',['forms'=>Form::latest()->paginate(20)]); }
 public function create(Request $request) { $key=$request->query('template'); if(!$key)return view('modules.forms.forms.templates',['templates'=>FormTemplates::all()]); $tpl=FormTemplates::get($key); return view('modules.forms.forms.form',['form'=>new Form($tpl['defaults']??['type'=>'lead','status'=>'draft','fields'=>[]]),'templateKey'=>$key]); }
 public function store(FormRequest $request) { $form=Form::create([...$request->formPayload(),'tenant_id'=>currentTenant()->id,'form_site_id'=>$this->sites->defaultFor(currentTenant())->id]); return redirect()->route('forms.forms.edit',$form)->with('success','Form created.'); }
 public function edit(Form $form) { return view('modules.forms.forms.form',['form'=>$form,'templateKey'=>null]); }
 public function update(FormRequest $request,Form $form) { $form->update($request->formPayload()); return back()->with('success','Form saved.'); }
 public function destroy(Form $form) { $form->delete(); return redirect()->route('forms.forms.index')->with('success','Form deleted.'); }
}
