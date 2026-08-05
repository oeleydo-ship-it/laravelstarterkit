<div class="card stat-card">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">API Tokens</h5>
        <p class="text-muted small">
            For the chat REST API at <code>/api/chat/conversations</code>. Send as
            <code>Authorization: Bearer &lt;token&gt;</code>.
        </p>

        @if(session('new_api_token'))
            <div class="alert alert-success small">
                <div class="fw-medium mb-1">Your new token — copy it now, it will not be shown again:</div>
                <code>{{ session('new_api_token') }}</code>
            </div>
        @endif

        <form method="POST" action="{{ route('chat.settings.tokens.store') }}" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-6">
                <label for="token-name" class="form-label fw-medium small mb-1">Token name</label>
                <input type="text" name="name" id="token-name" class="form-control form-control-sm"
                       maxlength="60" placeholder="CRM sync" required>
                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Create token</button>
            </div>
        </form>

        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Last used</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($apiTokens as $token)
                    <tr>
                        <td class="fw-medium">{{ $token->name }}</td>
                        <td class="text-muted small">{{ $token->created_at->toDateString() }}</td>
                        <td class="text-muted small">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('chat.settings.tokens.destroy', $token) }}"
                                  onsubmit="return confirm('Revoke this token? Anything using it will stop working.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted small py-3">No API tokens yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
