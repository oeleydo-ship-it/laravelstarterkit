<?php

namespace App\Services\SocialProof;

use Illuminate\Support\Facades\File;

class PublicAssetService
{
    public function paths(): array
    {
        $manifestPath = public_path('build/manifest.json');
        if (! File::exists($manifestPath)) {
            return ['js' => '', 'css' => null];
        }

        $manifest = json_decode(File::get($manifestPath), true) ?: [];
        $entry = $manifest['resources/js/sp/loader.js'] ?? null;

        if (! is_array($entry) || empty($entry['file'])) {
            foreach ($manifest as $item) {
                if (is_array($item) && str_starts_with(basename($item['file'] ?? ''), 'sp-') && str_ends_with($item['file'], '.js')) {
                    $entry = $item;
                    break;
                }
            }
        }

        return is_array($entry) && ! empty($entry['file'])
            ? [
                'js' => public_path('build/'.$entry['file']),
                'css' => empty($entry['css'][0]) ? null : public_path('build/'.$entry['css'][0]),
            ]
            : ['js' => '', 'css' => null];
    }

    public function javascript(): string
    {
        $path = $this->paths()['js'];

        return $path && File::exists($path) ? File::get($path) : '/* unavailable */';
    }

    public function stylesheet(): string
    {
        $path = $this->paths()['css'];

        return $path && File::exists($path) ? File::get($path) : '';
    }
}
