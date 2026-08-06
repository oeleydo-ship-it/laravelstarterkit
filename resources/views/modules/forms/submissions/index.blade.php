@extends('layouts.app')
@section('title','Form submissions')
@section('content')
@include('modules.forms._nav')
<div class="d-flex justify-content-between mb-4"><h4>Submissions</h4><a class="btn btn-outline-secondary" href="{{ route('forms.submissions.export') }}">Export CSV</a></div>
<form class="mb-3"><select name="form" class="form-select w-auto d-inline" onchange="this.form.submit()"><option value="">All forms</option>@foreach($forms as $form)<option value="{{ $form->id }}" @selected(request('form')==$form->id)>{{ $form->name }}</option>@endforeach</select></form>
<div class="table-card"><table class="table"><thead><tr><th>Form</th><th>Name</th><th>Email</th><th>Answers</th><th>When</th></tr></thead><tbody>@forelse($submissions as $submission)<tr><td>{{ $submission->form?->name }}</td><td>{{ $submission->name }}</td><td>{{ $submission->email }}</td><td><small>{{ json_encode($submission->answers) }}</small></td><td>{{ $submission->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="5">No submissions yet.</td></tr>@endforelse</tbody></table></div>{{ $submissions->links() }}
@endsection
