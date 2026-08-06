<?php
namespace App\Http\Controllers\Forms;
use App\Http\Controllers\Controller; use App\Models\Form; use App\Models\FormSubmission; use App\Services\Forms\SiteService;
class DashboardController extends Controller {
 public function __construct(protected SiteService $sites) { $this->middleware(fn($r,$n)=>($this->authorize('viewAny',Form::class) ? $n($r) : null)); }
 public function index() { $site=$this->sites->defaultFor(currentTenant()); $stats=['live'=>Form::where('status','live')->count(),'forms'=>Form::count(),'submissions'=>FormSubmission::count()]; return view('modules.forms.dashboard',['site'=>$site,'stats'=>$stats,'recentForms'=>Form::latest()->limit(8)->get(),'recentSubmissions'=>FormSubmission::with('form')->latest()->limit(8)->get()]); }
}
