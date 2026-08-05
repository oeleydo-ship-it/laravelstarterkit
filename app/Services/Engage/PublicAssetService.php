<?php

namespace App\Services\Engage;

use Illuminate\Support\Facades\File;

class PublicAssetService
{
    /**
     * Resolve the opaque built JS (+ optional CSS) for the public runtime.
     *
     * @return array{js: string, css: ?string}
     */
    public function paths(): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            return ['js' => '', 'css' => null];
        }

        $manifest = json_decode(File::get($manifestPath), true) ?: [];
        $entry = $manifest['resources/js/x/loader.js'] ?? null;

        if (! is_array($entry) || empty($entry['file'])) {
            // Fallback: first asset matching x-*.js
            foreach ($manifest as $item) {
                if (is_array($item) && isset($item['file']) && str_starts_with(basename($item['file']), 'x-') && str_ends_with($item['file'], '.js')) {
                    $entry = $item;
                    break;
                }
            }
        }

        if (! is_array($entry) || empty($entry['file'])) {
            return ['js' => '', 'css' => null];
        }

        $css = null;
        if (! empty($entry['css'][0])) {
            $css = public_path('build/'.$entry['css'][0]);
        }

        return [
            'js' => public_path('build/'.$entry['file']),
            'css' => $css,
        ];
    }

    public function javascript(): string
    {
        $path = $this->paths()['js'];

        return ($path && File::exists($path)) ? File::get($path) : '/* unavailable */';
    }

    public function stylesheet(): string
    {
        $path = $this->paths()['css'];

        return ($path && File::exists($path)) ? File::get($path) : '';
    }
}
