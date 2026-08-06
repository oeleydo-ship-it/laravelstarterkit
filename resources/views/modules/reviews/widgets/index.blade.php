@extends('layouts.app')
@section('title', 'Review Widgets')
@section('content')
@include('modules.reviews._nav')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="fw-bold">Widgets</h4>@can('create', App\Models\ReviewWidget::class)<a class="btn btn-primary" href="{{ route('reviews.widgets.create') }}">New widget</a>@endcan</div>
<div class="card"><table class="table mb-0"><thead><tr><th>Name</th><th>Layout</th><th>Rating</th><th>Status</th><th></th></tr></thead><tbody>@forelse($widgets as $widget)<tr><td>{{ $widget->name }}</td><td>{{ ucfirst($widget->layout) }}</td><td>{{ $widget->min_rating }}+ stars</td><td>{{ ucfirst($widget->status) }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('reviews.widgets.edit',$widget) }}">Edit</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No widgets yet.</td></tr>@endforelse</tbody></table></div>{{ $widgets->links() }}
@endsection
