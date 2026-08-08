<?php
namespace App\Events;
use App\Models\AutoblogPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class AutoblogPostUpdated implements ShouldBroadcastNow {
 use Dispatchable,InteractsWithSockets,SerializesModels;
 public function __construct(public AutoblogPost $post){}
 public function broadcastOn(): array{return [new PrivateChannel("tenant.{$this->post->tenant_id}.autoblog")];}
 public function broadcastAs(): string{return 'autoblog.post.updated';}
 public function broadcastWith(): array{return ['id'=>$this->post->id,'status'=>$this->post->status,'error'=>$this->post->display_error,'external_url'=>$this->post->external_url,'updated_at'=>$this->post->updated_at?->toIso8601String()];}
}
