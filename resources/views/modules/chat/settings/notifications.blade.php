<div class="card stat-card">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Notifications &amp; Webhooks</h5>
        <p class="text-muted small">
            Where your team hears about new chats, and where chat events are posted for your own systems.
        </p>

        <form method="POST" action="{{ route('chat.settings.integrations') }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="mail_enabled" value="1"
                           id="mail-enabled" {{ old('mail_enabled', $integrations['mail_enabled']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="mail-enabled">
                        Email agents when a chat starts or is assigned to them
                    </label>
                </div>
            </div>

            <div class="col-md-6">
                <label for="slack-url" class="form-label fw-medium small mb-1">Slack incoming webhook</label>
                <input type="url" name="slack_webhook_url" id="slack-url" class="form-control"
                       value="{{ old('slack_webhook_url', $integrations['slack_webhook_url']) }}"
                       placeholder="https://hooks.slack.com/services/…">
                @error('slack_webhook_url') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="discord-url" class="form-label fw-medium small mb-1">Discord webhook</label>
                <input type="url" name="discord_webhook_url" id="discord-url" class="form-control"
                       value="{{ old('discord_webhook_url', $integrations['discord_webhook_url']) }}"
                       placeholder="https://discord.com/api/webhooks/…">
                @error('discord_webhook_url') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="telegram-token" class="form-label fw-medium small mb-1">Telegram bot token</label>
                <input type="text" name="telegram_bot_token" id="telegram-token" class="form-control"
                       value="{{ old('telegram_bot_token', $integrations['telegram_bot_token']) }}">
                @error('telegram_bot_token') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="telegram-chat" class="form-label fw-medium small mb-1">Telegram chat ID</label>
                <input type="text" name="telegram_chat_id" id="telegram-chat" class="form-control"
                       value="{{ old('telegram_chat_id', $integrations['telegram_chat_id']) }}">
                @error('telegram_chat_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-8">
                <label for="webhook-url" class="form-label fw-medium small mb-1">Outbound webhook endpoint</label>
                <input type="url" name="webhook_url" id="webhook-url" class="form-control"
                       value="{{ old('webhook_url', $integrations['webhook_url']) }}"
                       placeholder="https://your-app.example.com/hooks/chat">
                <div class="form-text">
                    Receives <code>conversation.created</code>, <code>conversation.assigned</code>,
                    <code>conversation.closed</code> and <code>message.created</code> as JSON.
                </div>
                @error('webhook_url') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label for="webhook-secret" class="form-label fw-medium small mb-1">Signing secret</label>
                <input type="text" name="webhook_secret" id="webhook-secret" class="form-control"
                       value="{{ old('webhook_secret', $integrations['webhook_secret']) }}">
                <div class="form-text">
                    Sent as <code>X-Chat-Signature: sha256=…</code>. Generated for you if left blank.
                </div>
                @error('webhook_secret') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Notifications</button>
            </div>
        </form>
    </div>
</div>
