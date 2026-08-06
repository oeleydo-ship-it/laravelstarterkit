<?php
namespace App\Http\Controllers\Forms;
use App\Http\Controllers\Controller; use App\Models\Form; use App\Models\FormSubmission; use Illuminate\Http\Request;
class SubmissionController extends Controller {
 public function __construct() { $this->middleware(function($r,$next){$this->authorize('viewAny',Form::class); return $next($r);}); }
 public function index(Request $request) { $q=FormSubmission::with('form')->latest(); if($request->filled('form'))$q->where('form_id',$request->query('form')); return view('modules.forms.submissions.index',['submissions'=>$q->paginate(30)->withQueryString(),'forms'=>Form::orderBy('name')->get()]); }
 public function export() { return response()->streamDownload(function(){ $out=fopen('php://output','w'); fputcsv($out,['Form','Name','Email','Answers','Page URL','Submitted']); FormSubmission::with('form')->orderBy('id')->each(fn($s)=>fputcsv($out,[$s->form?->name,$s->name,$s->email,json_encode($s->answers),$s->page_url,$s->created_at])); fclose($out); },'form-submissions.csv',['Content-Type'=>'text/csv']); }
}
