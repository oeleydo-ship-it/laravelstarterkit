@extends('layouts.app')

@section('title', 'Modules')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">Manage Modules</h4>
    <p class="text-muted">Enable or disable modules for your workspace.</p>
</div>

<div class="row g-4">
    @foreach($modules as $module)
    <div class="col-md-6">
        <div class="card stat-card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-1">{{ $module->name }}</h5>
                        <p class="text-muted small mb-0">{{ $module->description }}</p>
                    </div>

                    <form method="POST" action="{{ route('modules.toggle') }}">
                        @csrf
                        <input type="hidden" name="module_key" value="{{ $module->key }}">
                        @php $isEnabled = $tenantModules[$module->key] ?? false; @endphp

                        <div class="form-check form-switch">
                            <input type="hidden" name="enabled" value="{{ $isEnabled ? '0' : '1' }}">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   {{ $isEnabled ? 'checked' : '' }}
                                   onchange="this.form.submit()"
                                   style="width:3em;height:1.5em;cursor:pointer;">
                        </div>
                    </form>
                </div>

                <div class="mt-3">
                    @if($isEnabled)
                        <span class="badge bg-success">Enabled</span>
                    @else
                        <span class="badge bg-secondary">Disabled</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
