<?php
namespace App\Http\Controllers\Autoblog;
use App\Http\Controllers\Controller; use App\Models\AutoblogDestination; use App\Models\AutoblogPost; use App\Services\Chat\AiSettingsService;
class DashboardController extends Controller { public function index(AiSettingsService $settings){ return view('autoblog.dashboard',['posts'=>AutoblogPost::with('destination')->latest()->paginate(15),'destinations'=>AutoblogDestination::orderBy('name')->get(),'ai'=>$settings->forForm(currentTenant()),'providers'=>AiSettingsService::providers(),'openaiModels'=>AiSettingsService::openaiModels(),'kimiModels'=>AiSettingsService::kimiModels()]); } }
