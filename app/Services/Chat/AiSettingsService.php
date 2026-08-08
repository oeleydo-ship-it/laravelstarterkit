<?php

namespace App\Services\Chat;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Chat\Ai\AiProvider;
use App\Services\Chat\Ai\AnthropicAiProvider;
use App\Services\Chat\Ai\NullAiProvider;
use App\Services\Chat\Ai\OpenAiCompatibleAiProvider;

class AiSettingsService
{
    public const SETTING_KEY = 'chat_ai';

    public static function providers(): array
    {
        return [
            'null' => 'Off (excerpt matching only)',
            'openai' => 'OpenAI',
            'kimi' => 'Kimi K3 (Moonshot)',
            'anthropic' => 'Anthropic Claude',
        ];
    }

    public static function openaiModels(): array
    {
        return [
            'gpt-4o-mini' => 'GPT-4o mini',
            'gpt-4o' => 'GPT-4o',
            'gpt-4.1-mini' => 'GPT-4.1 mini',
            'gpt-4.1' => 'GPT-4.1',
        ];
    }

    public static function kimiModels(): array
    {
        return [
            'kimi-k3' => 'Kimi K3',
            'kimi-k2.6' => 'Kimi K2.6',
            'kimi-k2.7-code' => 'Kimi K2.7 Code',
            'kimi-k2.7-code-highspeed' => 'Kimi K2.7 Code Highspeed',
        ];
    }

    public static function defaults(): array
    {
        return [
            'provider' => 'null',
            'openai' => [
                'key' => null,
                'model' => 'gpt-4o-mini',
                'base_url' => 'https://api.openai.com/v1',
            ],
            'kimi' => [
                'key' => null,
                'model' => 'kimi-k3',
                'base_url' => 'https://api.moonshot.ai/v1',
            ],
            'anthropic' => [
                'key' => null,
                'model' => config('chat.ai.anthropic.model', 'claude-sonnet-5'),
                'base_url' => config('chat.ai.anthropic.base_url', 'https://api.anthropic.com'),
            ],
        ];
    }

    public function for(?Tenant $tenant): array
    {
        $defaults = self::defaults();

        if (! $tenant) {
            return $this->mergeEnvFallback($defaults);
        }

        $stored = json_decode((string) Setting::get(self::SETTING_KEY, $tenant->id), true);
        $settings = $defaults;

        if (is_array($stored)) {
            if (isset($stored['provider']) && array_key_exists($stored['provider'], self::providers())) {
                $settings['provider'] = $stored['provider'];
            }

            foreach (['openai', 'kimi', 'anthropic'] as $vendor) {
                if (! empty($stored[$vendor]) && is_array($stored[$vendor])) {
                    $settings[$vendor] = array_merge($settings[$vendor], array_intersect_key(
                        $stored[$vendor],
                        $settings[$vendor]
                    ));
                }
            }
        }

        return $this->mergeEnvFallback($settings);
    }

    /**
     * Settings for the UI — API keys are masked so they never round-trip in HTML.
     */
    public function forForm(?Tenant $tenant): array
    {
        $settings = $this->for($tenant);

        foreach (['openai', 'kimi', 'anthropic'] as $vendor) {
            $key = $settings[$vendor]['key'] ?? null;
            $settings[$vendor]['key_set'] = filled($key);
            $settings[$vendor]['key_hint'] = filled($key)
                ? '••••'.substr((string) $key, -4)
                : null;
            unset($settings[$vendor]['key']);
        }

        return $settings;
    }

    public function save(Tenant $tenant, array $input): array
    {
        $current = $this->for($tenant);

        $provider = $input['provider'] ?? 'null';
        if (! array_key_exists($provider, self::providers())) {
            $provider = 'null';
        }

        $next = [
            'provider' => $provider,
            'openai' => [
                'key' => $this->resolveKey($input['openai_key'] ?? null, $current['openai']['key'] ?? null),
                'model' => $input['openai_model'] ?? $current['openai']['model'],
                'base_url' => rtrim($input['openai_base_url'] ?? $current['openai']['base_url'], '/'),
            ],
            'kimi' => [
                'key' => $this->resolveKey($input['kimi_key'] ?? null, $current['kimi']['key'] ?? null),
                'model' => $input['kimi_model'] ?? $current['kimi']['model'],
                'base_url' => rtrim($input['kimi_base_url'] ?? $current['kimi']['base_url'], '/'),
            ],
            'anthropic' => [
                'key' => $this->resolveKey($input['anthropic_key'] ?? null, $current['anthropic']['key'] ?? null),
                'model' => $input['anthropic_model'] ?? $current['anthropic']['model'],
                'base_url' => rtrim($input['anthropic_base_url'] ?? $current['anthropic']['base_url'], '/'),
            ],
        ];

        Setting::set(self::SETTING_KEY, $next, $tenant->id);

        return $this->for($tenant);
    }

    public function makeProvider(?Tenant $tenant = null): AiProvider
    {
        $settings = $this->for($tenant);
        $maxTokens = (int) config('chat.ai.max_tokens', 8192);

        return match ($settings['provider']) {
            'openai' => new OpenAiCompatibleAiProvider([
                'key' => $settings['openai']['key'],
                'model' => $settings['openai']['model'],
                'base_url' => $settings['openai']['base_url'],
                'max_tokens' => $maxTokens,
            ]),
            'kimi' => new OpenAiCompatibleAiProvider([
                'key' => $settings['kimi']['key'],
                'model' => $settings['kimi']['model'],
                'base_url' => $settings['kimi']['base_url'],
                'max_tokens' => $maxTokens,
            ]),
            'anthropic' => new AnthropicAiProvider([
                'key' => $settings['anthropic']['key'] ?: config('chat.ai.anthropic.key'),
                'model' => $settings['anthropic']['model'] ?: config('chat.ai.anthropic.model'),
                'base_url' => $settings['anthropic']['base_url'] ?: config('chat.ai.anthropic.base_url'),
                'max_tokens' => $maxTokens,
                'version' => config('chat.ai.anthropic.version', '2023-06-01'),
            ]),
            default => new NullAiProvider,
        };
    }

    protected function resolveKey(?string $incoming, ?string $existing): ?string
    {
        $incoming = is_string($incoming) ? trim($incoming) : null;

        if ($incoming === null || $incoming === '' || str_starts_with($incoming, '••••')) {
            return $existing;
        }

        return $incoming;
    }

    /**
     * When the workspace has not saved keys yet, fall back to .env so existing
     * deployments keep working.
     */
    protected function mergeEnvFallback(array $settings): array
    {
        $envProvider = config('chat.ai.provider', 'null');

        if (($settings['provider'] ?? 'null') === 'null' && in_array($envProvider, ['openai', 'kimi', 'anthropic'], true)) {
            $settings['provider'] = $envProvider;
        }

        if (blank($settings['openai']['key'] ?? null)) {
            $settings['openai']['key'] = config('chat.ai.openai.key');
        }
        if (blank($settings['kimi']['key'] ?? null)) {
            $settings['kimi']['key'] = config('chat.ai.kimi.key');
        }
        if (blank($settings['anthropic']['key'] ?? null)) {
            $settings['anthropic']['key'] = config('chat.ai.anthropic.key');
        }

        return $settings;
    }
}
