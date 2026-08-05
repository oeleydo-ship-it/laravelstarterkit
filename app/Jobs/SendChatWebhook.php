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
 * Machine-to-machine delivery of a chat event to the workspace's own endpoint.
 * The body is signed so the receiver can prove the request came from us and was
 * not modified in transit.
 */
class SendChatWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public const SIGNATURE_HEADER = 'X-Chat-Signature';

    public function __construct(
        public int $tenantId,
        public string $event,
        public array $payload,
    ) {
    }

    public function handle(IntegrationSettingsService $settings): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $config = $settings->for($tenant);

        if (blank($config['webhook_url'])) {
            return;
        }

        $body = json_encode([
            'event' => $this->event,
            'tenant' => $tenant->slug,
            'sent_at' => now()->toIso8601String(),
            'data' => $this->payload,
        ]);

        // Signed over the exact bytes sent, so the receiver can recompute it
        // without having to reserialize (and risk a different key order).
        $signature = hash_hmac('sha256', $body, (string) $config['webhook_secret']);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                self::SIGNATURE_HEADER => "sha256={$signature}",
            ])
                ->timeout(config('chat.webhooks.timeout'))
                ->withBody($body, 'application/json')
                ->post($config['webhook_url']);

            if ($response->failed()) {
                Log::warning('Chat webhook delivery failed', [
                    'tenant_id' => $this->tenantId,
                    'event' => $this->event,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Chat webhook delivery errored', [
                'tenant_id' => $this->tenantId,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
