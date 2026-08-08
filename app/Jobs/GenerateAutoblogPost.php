<?php
namespace App\Jobs;
use App\Events\AutoblogPostUpdated;
use App\Models\AutoblogPost;
use App\Models\Tenant;
use App\Services\Autoblog\ContentGenerator;
use App\Services\Autoblog\ProviderError;
use App\Services\Chat\AiSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
class GenerateAutoblogPost implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int $tries=3; public int $timeout=120; public array $backoff=[10,30,90];
 public function __construct(public int $postId){$this->onQueue('autoblog');}
 public function handle(AiSettingsService $settings): void {
  $post=AutoblogPost::withoutGlobalScopes()->findOrFail($this->postId);$tenant=Tenant::findOrFail($post->tenant_id);
  $post->update(['status'=>'generating','attempt_count'=>$post->attempt_count+1,'last_error'=>null]);$this->announce($post);
  try{$article=(new ContentGenerator($settings->makeProvider($tenant)))->generate($post->topic,$post->tone,$post->keywords??'');$hasDestination=$post->destination_id||filled($post->destination_url);$status=$hasDestination&&$post->scheduled_at&&$post->scheduled_at->isFuture()?'scheduled':'draft';$post->update(array_merge($article,['status'=>$status,'last_error'=>null]));$this->announce($post->fresh());}
  catch(Throwable $e){$post->update(['status'=>'failed','last_error'=>ProviderError::friendly($e)]);$this->announce($post->fresh());throw $e;}
 }
 public function failed(?Throwable $e): void {if($post=AutoblogPost::withoutGlobalScopes()->find($this->postId)){$post->update(['status'=>'failed','last_error'=>ProviderError::friendly($e??'failed')]);$this->announce($post->fresh());}}
 private function announce(AutoblogPost $post): void {try{broadcast(new AutoblogPostUpdated($post));}catch(Throwable $e){report($e);}}
}
