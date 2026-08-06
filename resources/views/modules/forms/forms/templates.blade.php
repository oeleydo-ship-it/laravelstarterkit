@extends('layouts.app')
@section('title','Choose a template')
@section('content')
@include('modules.forms._nav')
<h4 class="mb-4">Choose a template</h4><div class="row g-3">@foreach($templates as $key=>$template)<div class="col-md-4"><div class="card h-100"><div class="card-body"><h5>{{ $template['label'] }}</h5><p class="text-muted">{{ $template['defaults']['name'] }}</p><a class="btn btn-primary" href="{{ route('forms.forms.create',['template'=>$key]) }}">Use template</a></div></div></div>@endforeach</div>
@endsection
