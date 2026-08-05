<div class="card stat-card">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Knowledge Base</h5>
        <p class="text-muted small mb-4">
            Upload documents (PDF, Word, text) that agents can search in the composer.
            When auto-reply is on, the assistant answers visitors from this content until an agent accepts the chat.
            @if($aiAvailable)
                <span class="text-success">AI provider is configured — drafts and auto-replies will use it.</span>
            @else
                <span class="text-danger">No AI provider is configured yet. Choose OpenAI or Kimi K3 below, or auto-replies will paste matching excerpts.</span>
            @endif
        </p>

        <form method="POST" action="{{ route('chat.settings.knowledge') }}" class="mb-4" id="kb-ai-settings-form">
            @csrf @method('PUT')

            <h6 class="fw-semibold mb-3">AI provider</h6>
            <div class="mb-3">
                <label class="form-label fw-medium" for="ai_provider">Provider</label>
                <select name="provider" id="ai_provider" class="form-select">
                    @foreach($aiProviders as $value => $label)
                        <option value="{{ $value }}" @selected(old('provider', $aiSettings['provider']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="border rounded p-3 mb-3 ai-provider-pane" data-provider="openai"
                 style="{{ old('provider', $aiSettings['provider']) === 'openai' ? '' : 'display:none' }}">
                <div class="fw-semibold mb-2">OpenAI</div>
                <div class="mb-3">
                    <label class="form-label">API key</label>
                    <input type="password" name="openai_key" class="form-control" autocomplete="off"
                           placeholder="{{ $aiSettings['openai']['key_hint'] ?? 'sk-…' }}">
                    @if($aiSettings['openai']['key_set'] ?? false)
                        <div class="form-text">Key saved ({{ $aiSettings['openai']['key_hint'] }}). Leave blank to keep it.</div>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <select name="openai_model" class="form-select">
                            @foreach($openaiModels as $value => $label)
                                <option value="{{ $value }}" @selected(old('openai_model', $aiSettings['openai']['model']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Base URL</label>
                        <input type="url" name="openai_base_url" class="form-control"
                               value="{{ old('openai_base_url', $aiSettings['openai']['base_url']) }}">
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3 ai-provider-pane" data-provider="kimi"
                 style="{{ old('provider', $aiSettings['provider']) === 'kimi' ? '' : 'display:none' }}">
                <div class="fw-semibold mb-2">Kimi K3 (Moonshot)</div>
                <p class="text-muted small mb-3">
                    Get a key at <a href="https://platform.kimi.ai/console/api-keys" target="_blank" rel="noopener">platform.kimi.ai</a>.
                    API is OpenAI-compatible at <code>https://api.moonshot.ai/v1</code>.
                </p>
                <div class="mb-3">
                    <label class="form-label">API key</label>
                    <input type="password" name="kimi_key" class="form-control" autocomplete="off"
                           placeholder="{{ $aiSettings['kimi']['key_hint'] ?? 'Moonshot / Kimi API key' }}">
                    @if($aiSettings['kimi']['key_set'] ?? false)
                        <div class="form-text">Key saved ({{ $aiSettings['kimi']['key_hint'] }}). Leave blank to keep it.</div>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <select name="kimi_model" class="form-select">
                            @foreach($kimiModels as $value => $label)
                                <option value="{{ $value }}" @selected(old('kimi_model', $aiSettings['kimi']['model']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Base URL</label>
                        <input type="url" name="kimi_base_url" class="form-control"
                               value="{{ old('kimi_base_url', $aiSettings['kimi']['base_url']) }}">
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3 ai-provider-pane" data-provider="anthropic"
                 style="{{ old('provider', $aiSettings['provider']) === 'anthropic' ? '' : 'display:none' }}">
                <div class="fw-semibold mb-2">Anthropic Claude</div>
                <div class="mb-3">
                    <label class="form-label">API key</label>
                    <input type="password" name="anthropic_key" class="form-control" autocomplete="off"
                           placeholder="{{ $aiSettings['anthropic']['key_hint'] ?? 'sk-ant-…' }}">
                    @if($aiSettings['anthropic']['key_set'] ?? false)
                        <div class="form-text">Key saved ({{ $aiSettings['anthropic']['key_hint'] }}). Leave blank to keep it.</div>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Model</label>
                        <input type="text" name="anthropic_model" class="form-control"
                               value="{{ old('anthropic_model', $aiSettings['anthropic']['model']) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Base URL</label>
                        <input type="url" name="anthropic_base_url" class="form-control"
                               value="{{ old('anthropic_base_url', $aiSettings['anthropic']['base_url']) }}">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="auto_reply"
                       name="auto_reply" value="1" @checked(old('auto_reply', $autoReplyEnabled))>
                <label class="form-check-label" for="auto_reply">
                    Auto-answer visitors from the knowledge base (while chat is unassigned)
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Save AI &amp; auto-reply settings</button>
        </form>

        <script>
            (function () {
                const select = document.getElementById('ai_provider');
                if (!select) return;
                const panes = document.querySelectorAll('.ai-provider-pane');
                const sync = () => {
                    panes.forEach((pane) => {
                        pane.style.display = pane.dataset.provider === select.value ? '' : 'none';
                    });
                };
                select.addEventListener('change', sync);
                sync();
            })();
        </script>

        <hr class="my-4">

        <h6 class="fw-semibold mb-3">Upload document</h6>
        <form method="POST" action="{{ route('chat.settings.documents.store') }}" enctype="multipart/form-data" class="mb-4">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-medium">Title (optional)</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                           placeholder="Refund policy">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-medium">File</label>
                    <input type="file" name="document" class="form-control" required
                           accept=".{{ implode(',.', $kbExtensions) }}">
                    <div class="form-text">
                        Allowed: {{ strtoupper(implode(', ', $kbExtensions)) }} · max {{ number_format($kbMaxKb / 1024, 0) }} MB
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Upload</button>
                </div>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0">Uploaded documents</h6>
            <a href="{{ route('chat.articles.index') }}" class="small">Manage text articles →</a>
        </div>

        @forelse($documents as $document)
            <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                <div>
                    <div class="fw-medium">
                        {{ $document->title }}
                        @if(! $document->is_active)
                            <span class="badge bg-secondary">Inactive</span>
                        @elseif(! $document->extracted_text)
                            <span class="badge bg-warning text-dark">No searchable text</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                    </div>
                    <div class="text-muted small">
                        {{ $document->original_name }} · {{ $document->humanSize() }} ·
                        uploaded {{ $document->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('chat.settings.documents.download', $document) }}"
                       class="btn btn-sm btn-outline-secondary">Download</a>
                    <form method="POST" action="{{ route('chat.settings.documents.toggle', $document) }}">
                        @csrf @method('PUT')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            {{ $document->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('chat.settings.documents.destroy', $document) }}"
                          onsubmit="return confirm('Remove this document from the knowledge base?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No documents yet. Upload a PDF or text file to get started.</p>
        @endforelse
    </div>
</div>
