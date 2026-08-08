<?php
namespace App\Services\Autoblog;
use App\Models\AutoblogDestination;
use App\Models\AutoblogPost;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class Publisher {
    public function publishToUrl(AutoblogPost $post, string $url): array {
        $response=Http::acceptJson()->timeout(45)->post($url,array_merge($this->payload($post),['source'=>'autoblog']));
        if ($response->failed()) throw new RuntimeException('Publishing endpoint returned '.$response->status().': '.str($response->body())->limit(300));
        return $this->result($response);
    }

    public function publish(AutoblogPost $post, AutoblogDestination $destination): array {
        $payload=$this->payload($post);
        if ($destination->type==='wordpress') {
            $response=Http::withBasicAuth($destination->username ?? '',$destination->secret ?? '')->acceptJson()->timeout(45)->post(rtrim($destination->base_url,'/').'/wp-json/wp/v2/posts',$payload);
            if ($response->failed()) throw new RuntimeException('WordPress returned '.$response->status().': '.str($response->body())->limit(300));
            return ['id'=>(string)$response->json('id'),'url'=>$response->json('link')];
        }
        $response=Http::when(filled($destination->secret),fn($http)=>$http->withToken($destination->secret))->acceptJson()->timeout(45)->post($destination->base_url,array_merge($payload,['source'=>'autoblog']));
        if ($response->failed()) throw new RuntimeException('Publishing endpoint returned '.$response->status().': '.str($response->body())->limit(300));
        return $this->result($response);
    }
    private function payload(AutoblogPost $post): array {return ['title'=>$post->title,'slug'=>$post->slug,'content'=>$post->content,'excerpt'=>$post->excerpt,'status'=>'publish'];}
    private function result($response): array {return ['id'=>(string)($response->json('id') ?? $response->json('data.id') ?? ''),'url'=>$response->json('url') ?? $response->json('link')];}
}
