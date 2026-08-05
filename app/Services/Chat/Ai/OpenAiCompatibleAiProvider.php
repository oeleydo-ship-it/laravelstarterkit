<?php

namespace App\Services\Chat\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI Chat Completions API (also used by Kimi / Moonshot, which is
 * OpenAI-compatible — only the base URL and model name differ).
 */
class OpenAiCompatibleAiProvider implements AiProvider
{
    public function __construct(protected array $config)
    {
    }

    public function isConfigured(): bool
    {
        return filled($this->config['key'] ?? null)
            && filled($this->config['model'] ?? null)
            && filled($this->config['base_url'] ?? null);
    }

    public function complete(string $system, array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The AI provider is missing an API key, model, or base URL.');
        }

        $payloadMessages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages,
        );

        $response = Http::withToken($this->config['key'])
            ->acceptJson()
            ->timeout(45)
            ->post(rtrim($this->config['base_url'], '/').'/chat/completions', [
                'model' => $this->config['model'],
                'max_tokens' => (int) ($this->config['max_tokens'] ?? 600),
                'messages' => $payloadMessages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('The AI provider returned '.$response->status().'.');
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (blank($text)) {
            throw new RuntimeException('The AI provider returned an empty response.');
        }

        return trim($text);
    }
}
