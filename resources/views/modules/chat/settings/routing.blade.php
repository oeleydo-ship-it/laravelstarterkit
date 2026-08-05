<div class="card stat-card">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Conversation Routing</h5>
        <p class="text-muted small">How incoming chats are assigned to your team.</p>

        <form method="POST" action="{{ route('chat.settings.update') }}">
            @csrf
            @method('PUT')

            @foreach($strategies as $key => $label)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="routing_strategy"
                           id="strategy-{{ $key }}" value="{{ $key }}"
                           {{ $current === $key ? 'checked' : '' }}>
                    <label class="form-check-label" for="strategy-{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach

            @error('routing_strategy') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

            <p class="text-muted small mt-3 mb-3">
                Automatic routing only considers agents who have set themselves <strong>Online</strong> in the
                inbox. If nobody is online, chats stay unassigned until an agent picks them up.
            </p>

            <button type="submit" class="btn btn-primary">Save Routing</button>
        </form>
    </div>
</div>
