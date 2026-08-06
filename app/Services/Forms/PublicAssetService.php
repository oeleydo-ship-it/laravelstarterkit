<?php
namespace App\Services\Forms;
use Illuminate\Support\Facades\File;
class PublicAssetService {
 public function paths(): array { $path=public_path('build/manifest.json'); if(!File::exists($path))return ['js'=>'','css'=>null]; $manifest=json_decode(File::get($path),true)?:[]; $entry=$manifest['resources/js/f/loader.js']??null; if(!is_array($entry)||empty($entry['file']))foreach($manifest as $item)if(is_array($item)&&isset($item['file'])&&str_starts_with(basename($item['file']),'f-')&&str_ends_with($item['file'],'.js')){$entry=$item;break;} if(!is_array($entry)||empty($entry['file']))return ['js'=>'','css'=>null]; return ['js'=>public_path('build/'.$entry['file']),'css'=>!empty($entry['css'][0])?public_path('build/'.$entry['css'][0]):null]; }
 public function javascript(): string { $p=$this->paths()['js']; return $p&&File::exists($p)?File::get($p):'/* unavailable */'; }
 public function stylesheet(): string { $p=$this->paths()['css']; return $p&&File::exists($p)?File::get($p):''; }
}
