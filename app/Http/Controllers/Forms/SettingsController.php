<?php
namespace App\Http\Controllers\Forms;
use App\Http\Controllers\Controller; use App\Services\Forms\SiteService; use Illuminate\Http\Request;
class SettingsController extends Controller {
 public function __construct(protected SiteService $sites) { $this->middleware(function($r,$n){abort_unless($r->user()->hasPrivilege(\App\Support\Privileges::FORMS_MANAGE)||$r->user()->isOwnerOrAdmin(),403);return $n($r);}); }
 public function index(){ $site=$this->sites->defaultFor(currentTenant()); return view('modules.forms.settings',compact('site'))->with('snippet',$this->sites->embedSnippet($site));}
 public function install(){ $site=$this->sites->defaultFor(currentTenant()); return view('modules.forms.install',compact('site'))->with('snippet',$this->sites->embedSnippet($site));}
 public function update(Request $request){$data=$request->validate(['name'=>['required','string','max:120'],'brand_color'=>['nullable','regex:/^#[0-9A-Fa-f]{6}$/'],'allowed_origins'=>['nullable','string','max:5000']]);$this->sites->saveSettings($this->sites->defaultFor(currentTenant()),$data);return back()->with('success','Settings saved.');}
 public function rotateKey(){$this->sites->rotateKey($this->sites->defaultFor(currentTenant()));return redirect()->route('forms.install')->with('success','Install key rotated. Update your website snippet.');}
}
