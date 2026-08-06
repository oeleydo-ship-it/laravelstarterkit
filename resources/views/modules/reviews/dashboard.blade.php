@extends('layouts.app')
@section('title', 'Reviews')
@section('content')
@include('modules.reviews._nav')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-1">Reviews & Testimonials</h4><p class="text-muted mb-0">Collect, moderate, and display customer feedback.</p></div><a class="btn btn-primary" href="{{ route('reviews.widgets.create') }}">New widget</a></div>
<div class="row g-3 mb-4">@foreach([['Approved',$stats['approved']],['Pending',$stats['pending']],['Widgets',$stats['widgets']]] as [$label,$value])<div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ number_format($value) }}</div></div></div></div>@endforeach</div>
<div class="card"><div class="card-body"><div class="d-flex justify-content-between"><h6>Recent reviews</h6><a href="{{ route('reviews.index') }}">Moderate all</a></div><table class="table mb-0"><thead><tr><th>Author</th><th>Rating</th><th>Review</th><th>Status</th></tr></thead><tbody>@forelse($reviews as $review)<tr><td>{{ $review->author_name }}</td><td>{{ str_repeat('★', $review->rating) }}</td><td>{{ \Illuminate\Support\Str::limit($review->body, 80) }}</td><td>{{ ucfirst($review->status) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">No reviews yet.</td></tr>@endforelse</tbody></table></div></div>
@endsection
