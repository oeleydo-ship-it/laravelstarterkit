<?php
namespace App\Services\Autoblog;
use App\Models\AutoblogDestination;
use Illuminate\Support\Facades\Http;
use Throwable;
class DestinationVerifier {
 public function verify(AutoblogDestination $destination): array {
  try {
   $request=Http::acceptJson()->connectTimeout(10)->timeout(20);
   if(defined('CURLOPT_IPRESOLVE')&&defined('CURL_IPRESOLVE_V4'))$request=$request->withOptions(['curl'=>[CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4]]);
   if($destination->type==='wordpress'){
    $response=$request->withBasicAuth($destination->username??'',$destination->secret??'')->get(rtrim($destination->base_url,'/').'/wp-json/wp/v2/users/me',['context'=>'edit']);
   } else {
    $response=$request->when(filled($destination->secret),fn($http)=>$http->withToken($destination->secret))->get($destination->base_url);
   }
   if($response->successful())return ['verified_at'=>now(),'verification_error'=>null];
   return ['verified_at'=>null,'verification_error'=>'Connection returned HTTP '.$response->status().'. Check the URL and credentials.'];
  } catch(Throwable $e){return ['verified_at'=>null,'verification_error'=>'Could not connect to this destination. Check its URL, SSL certificate, firewall, and credentials.'];}
 }
}
