@extends('layouts.app')
@section('title','Forms')
@section('content')
@include('modules.forms._nav')
<div class="d-flex justify-content-between mb-4"><h4>Forms</h4>@can('create',App\Models\Form::class)<a class="btn btn-primary" href="{{ route('forms.forms.create') }}">New from template</a>@endcan</div>
<div class="table-card"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Submissions</th><th></th></tr></thead><tbody>@forelse($forms as $form)<tr><td>{{ $form->name }}</td><td>{{ $form->typeLabel() }}</td><td><span class="badge {{ $form->statusBadgeClass() }}">{{ $form->statusLabel() }}</span></td><td>{{ $form->submissions()->count() }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('forms.forms.edit',$form) }}">Edit</a></td></tr>@empty<tr><td colspan="5">No forms yet.</td></tr>@endforelse</tbody></table></div>{{ $forms->links() }}
@endsection
