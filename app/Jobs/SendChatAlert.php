<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Chat\IntegrationSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fan a short human-readable alert out to whichever chat tools the workspace
 * configured. Queued so a slow or down third party never delays the visitor's
 * request, and each destination is isolated so one failure does not stop the
 * others.
 */
class SendChatAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $tenantId,
        public string $text,
        public ?string $url = null,
    ) {
    }

    public function handle(IntegrationSettingsService $settings): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $config = $settings->for($tenant);
        $message = $this->url ? "{$this->text}\n{$this->url}" : $this->text;

        $this->post('Slack', $config['slack_webhook_url'], ['text' => $message]);
        $this->post('Discord', $config['discord_webhook_url'], ['content' => $message]);

        if (filled($config['telegram_bot_token']) && filled($config['telegram_chat_id'])) {
            $this->post(
                'Telegram',
                "https://api.telegram.org/bot{$config['telegram_bot_token']}/sendMessage",
                ['chat_id' => $config['telegram_chat_id'], 'text' => $message],
            );
        }
    }

    protected function post(string $destination, ?string $url, array $payload): void
    {
        if (blank($url)) {
            return;
        }

        try {
            $response = Http::timeout(config('chat.webhooks.timeout'))->post($url, $payload);

            if ($response->failed()) {
                Log::warning("Chat alert to {$destination} failed", [
                    'tenant_id' => $this->tenantId,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            // Never rethrow: an unreachable chat tool must not fail the job and
            // retry the *other* destinations along with it.
            Log::warning("Chat alert to {$destination} errored", [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
