<?php

namespace App\Services\Bookings;

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
        $entry = $manifest['resources/js/b/loader.js'] ?? null;

        if (! is_array($entry) || empty($entry['file'])) {
            foreach ($manifest as $item) {
                if (
                    is_array($item)
                    && isset($item['file'])
                    && str_starts_with(basename($item['file']), 'b-')
                    && str_ends_with($item['file'], '.js')
                ) {
                    $entry = $item;
                    break;
                }
            }
        }

        if (! is_array($entry) || empty($entry['file'])) {
            return ['js' => '', 'css' => null];
        }

        return [
            'js' => public_path('build/'.$entry['file']),
            'css' => ! empty($entry['css'][0]) ? public_path('build/'.$entry['css'][0]) : null,
        ];
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
