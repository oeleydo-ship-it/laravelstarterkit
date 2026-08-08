<?php

namespace App\Services\Chat\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
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

        try {
            $request = Http::withToken($this->config['key'])
                ->acceptJson()->connectTimeout(10)->timeout(75)
                ->retry(2, 350, throw: false);

            // Avoid broken IPv6 routes on dual-stack Windows/PHP installations.
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                $request = $request->withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                ]);
            }

            $response = $request->post(rtrim($this->config['base_url'], '/').'/chat/completions', [
                    'model' => $this->config['model'],
                    'max_tokens' => (int) ($this->config['max_tokens'] ?? 8192),
                    'messages' => $payloadMessages,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The AI provider could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            $detail = data_get($response->json(), 'error.message');
            throw new RuntimeException('The AI provider returned '.$response->status().($detail ? ': '.$detail : '.'));
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (blank($text)) {
            $finishReason = data_get($response->json(), 'choices.0.finish_reason');
            $reasoning = data_get($response->json(), 'choices.0.message.reasoning_content');

            if ($finishReason === 'length' || filled($reasoning)) {
                throw new RuntimeException('The AI provider exhausted the output token limit before returning the final answer.');
            }

            throw new RuntimeException('The AI provider returned an empty response.');
        }

        return trim($text);
    }
}
