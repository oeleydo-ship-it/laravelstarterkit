<?php
namespace App\Http\Controllers\Autoblog;
use App\Http\Controllers\Controller; use App\Models\AutoblogDestination; use App\Services\Autoblog\DestinationVerifier; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class DestinationController extends Controller {
 public function index(){return view('autoblog.destinations.index',['destinations'=>AutoblogDestination::orderBy('name')->get()]);}
 public function store(Request $r,DestinationVerifier $verifier){
  $data=$this->data($r);$data['is_active']=$r->boolean('is_active');
  $destination=AutoblogDestination::create($data);
  $destination->update($verifier->verify($destination));

  return redirect()->route('autoblog.dashboard')
   ->withInput(['destination_id'=>$destination->id])
   ->with($destination->verified_at?'success':'error',$destination->verified_at?'Destination connected, verified, and selected.':'Destination saved, but verification failed: '.$destination->verification_error);
 }
 public function verify(AutoblogDestination $destination,DestinationVerifier $verifier){$destination->update($verifier->verify($destination));return back()->with($destination->verified_at?'success':'error',$destination->verified_at?'Destination connection verified.':$destination->verification_error);}
 public function update(Request $r,AutoblogDestination $destination){$data=$this->data($r,true);if(blank($data['secret']??null))unset($data['secret']);$data['is_active']=$r->boolean('is_active');$destination->update($data);return back()->with('success','Destination updated.');}
 public function destroy(AutoblogDestination $destination){$destination->delete();return back()->with('success','Destination deleted.');}
 private function data(Request $r,bool $editing=false){return $r->validate(['name'=>'required|string|max:255','type'=>['required',Rule::in(['wordpress','webhook'])],'base_url'=>'required|url|max:1000','username'=>'nullable|string|max:255','secret'=>[$editing?'nullable':'required','string','max:2000']]);}
}
