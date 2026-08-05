<div class="card stat-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <h5 class="fw-bold mb-0">Widget Appearance</h5>
            <a href="{{ $widgetUrl }}" target="_blank" rel="noopener"
               class="btn btn-sm btn-outline-secondary">Preview widget</a>
        </div>
        <p class="text-muted small">What your visitors see on the public chat widget.</p>

        <form method="POST" action="{{ route('chat.settings.appearance') }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label for="widget-title" class="form-label fw-medium small mb-1">Header title</label>
                <input type="text" name="title" id="widget-title" class="form-control"
                       maxlength="60" value="{{ old('title', $appearance['title']) }}">
                <div class="form-text">Leave blank to use your workspace name.</div>
                @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label for="widget-launcher" class="form-label fw-medium small mb-1">Launcher button text</label>
                <input type="text" name="launcher_text" id="widget-launcher" class="form-control"
                       maxlength="30" value="{{ old('launcher_text', $appearance['launcher_text']) }}" required>
                @error('launcher_text') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-2">
                <label for="widget-color" class="form-label fw-medium small mb-1">Accent colour</label>
                <input type="color" name="color" id="widget-color" class="form-control form-control-color w-100"
                       value="{{ old('color', $appearance['color']) }}" required>
                @error('color') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="widget-greeting" class="form-label fw-medium small mb-1">Greeting</label>
                <textarea name="greeting" id="widget-greeting" class="form-control" rows="2"
                          maxlength="300">{{ old('greeting', $appearance['greeting']) }}</textarea>
                @error('greeting') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label for="widget-offline" class="form-label fw-medium small mb-1">Offline message</label>
                <textarea name="offline_message" id="widget-offline" class="form-control" rows="2"
                          maxlength="300">{{ old('offline_message', $appearance['offline_message']) }}</textarea>
                <div class="form-text">Shown outside your business hours.</div>
                @error('offline_message') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="pre_chat_enabled" value="1"
                           id="pre-chat-enabled"
                           {{ old('pre_chat_enabled', $appearance['pre_chat_enabled']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pre-chat-enabled">
                        Ask new visitors for their name and email before the chat starts
                    </label>
                </div>
                <div class="form-text">
                    Both fields stay optional for the visitor. Returning visitors are never asked again.
                </div>
            </div>

            <div class="col-md-6">
                <label for="pre-chat-message" class="form-label fw-medium small mb-1">Pre-chat prompt</label>
                <input type="text" name="pre_chat_message" id="pre-chat-message" class="form-control"
                       maxlength="200" value="{{ old('pre_chat_message', $appearance['pre_chat_message']) }}">
                @error('pre_chat_message') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Appearance</button>
            </div>
        </form>
    </div>
</div>
