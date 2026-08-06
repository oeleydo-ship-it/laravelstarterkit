@extends('layouts.app')
@section('title','Forms')
@section('content')
@include('modules.forms._nav')
<div class="d-flex justify-content-between mb-4"><div><h4>Forms & Surveys</h4><p class="text-muted mb-0">Collect leads, feedback, and answers.</p></div><a class="btn btn-primary" href="{{ route('forms.forms.create') }}">New form</a></div>
<div class="row g-3 mb-4">@foreach(['Live forms'=>$stats['live'],'All forms'=>$stats['forms'],'Submissions'=>$stats['submissions']] as $label=>$value)<div class="col-md-4"><div class="stat-card p-3"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div>@endforeach</div>
<div class="table-card"><table class="table"><thead><tr><th>Recent forms</th><th>Status</th><th>Updated</th></tr></thead><tbody>@forelse($recentForms as $form)<tr><td><a href="{{ route('forms.forms.edit',$form) }}">{{ $form->name }}</a></td><td><span class="badge {{ $form->statusBadgeClass() }}">{{ $form->statusLabel() }}</span></td><td>{{ $form->updated_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="3">No forms yet.</td></tr>@endforelse</tbody></table></div>
@endsection
