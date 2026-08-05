<?php

namespace App\Services\Chat\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicAiProvider implements AiProvider
{
    public function __construct(protected array $config)
    {
    }

    public function isConfigured(): bool
    {
        return filled($this->config['key'] ?? null) && filled($this->config['model'] ?? null);
    }

    public function complete(string $system, array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The Anthropic provider is missing an API key or model.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->config['key'],
            'anthropic-version' => $this->config['version'],
            'content-type' => 'application/json',
        ])
            ->timeout(30)
            ->post(rtrim($this->config['base_url'], '/').'/v1/messages', [
                'model' => $this->config['model'],
                'max_tokens' => $this->config['max_tokens'],
                'system' => $system,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            // The upstream body can carry account details, so it is logged by the
            // caller rather than surfaced — agents just see that assist is down.
            throw new RuntimeException('The AI provider returned '.$response->status().'.');
        }

        // Concatenate every text block; the API can split a reply across several.
        $text = collect($response->json('content') ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if (blank($text)) {
            throw new RuntimeException('The AI provider returned an empty response.');
        }

        return trim($text);
    }
}
