@extends('layouts.superadmin')
@section('title', 'Module Control')
@section('content')
<div class="mb-4"><h4 class="fw-bold mb-1">System Module Control</h4><p class="text-muted mb-0">Control which products are available across every workspace. Hidden or unavailable modules disappear from tenant navigation and cannot be accessed directly.</p></div>
<div class="row g-4">
@foreach($modules as $module)
<div class="col-xl-6"><form method="POST" action="{{ route('superadmin.modules.update', $module) }}" class="card stat-card h-100">@csrf @method('PUT')
<div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-start mb-3"><div><div class="text-muted small font-monospace">{{ $module->key }}</div><h5 class="fw-bold mb-0">{{ $module->name }}</h5></div><span class="badge {{ $module->is_active && $module->is_visible ? 'bg-success' : 'bg-secondary' }}">{{ $module->is_active && $module->is_visible ? 'Available' : 'Unavailable' }}</span></div>
    <div class="mb-3"><label class="form-label">Display name</label><input class="form-control" name="name" required value="{{ old('name', $module->name) }}"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2">{{ old('description', $module->description) }}</textarea></div>
    @foreach(['is_active' => ['Enable module system-wide', 'Disabling blocks all tenant and public module routes.'], 'is_visible' => ['Show to workspaces', 'Hiding removes it from tenant navigation and module selection.'], 'enabled_by_default' => ['Enable by default', 'New workspace module records start enabled.']] as $field => [$label, $help])
    <div class="form-check form-switch mb-3"><input type="hidden" name="{{ $field }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}_{{ $module->id }}" @checked($module->{$field})><label class="form-check-label fw-medium" for="{{ $field }}_{{ $module->id }}">{{ $label }}</label><div class="form-text">{{ $help }}</div></div>
    @endforeach
    <button class="btn btn-primary mt-2">Save module</button>
</div></form></div>
@endforeach
</div>
@endsection
