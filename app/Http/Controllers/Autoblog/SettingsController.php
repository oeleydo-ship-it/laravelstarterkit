<?php
namespace App\Http\Controllers\Autoblog;
use App\Http\Controllers\Controller; use App\Services\Chat\AiSettingsService; use Illuminate\Http\Request;
class SettingsController extends Controller { public function update(Request $r,AiSettingsService $settings){$data=$r->validate(['provider'=>'required|in:openai,kimi','openai_key'=>'nullable|string|max:1000','openai_model'=>'required|string|max:100','openai_base_url'=>'required|url','kimi_key'=>'nullable|string|max:1000','kimi_model'=>'required|string|max:100','kimi_base_url'=>'required|url']);$settings->save(currentTenant(),$data);return back()->with('success','Autoblog AI settings saved.');} }
