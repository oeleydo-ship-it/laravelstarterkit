<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatIntegrationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mail_enabled' => $this->boolean('mail_enabled')]);
    }

    public function rules(): array
    {
        // https only: these URLs receive conversation content, and the workspace
        // is choosing the destination, so plaintext delivery is never the intent.
        $webhook = ['nullable', 'url:https', 'max:500'];

        return [
            'mail_enabled' => 'boolean',
            'slack_webhook_url' => $webhook,
            'discord_webhook_url' => $webhook,
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
            'webhook_url' => $webhook,
            'webhook_secret' => 'nullable|string|min:16|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'slack_webhook_url.url' => 'The Slack webhook must be an https URL.',
            'discord_webhook_url.url' => 'The Discord webhook must be an https URL.',
            'webhook_url.url' => 'The webhook endpoint must be an https URL.',
        ];
    }
}
