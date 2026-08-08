@extends('layouts.app')
@section('title','Autoblog Posts')
@section('content')
<div class="d-flex justify-content-between align-items-end mb-4"><div><a href="{{ route('autoblog.dashboard') }}" class="small text-muted text-decoration-none">&larr; AI Autoblog</a><h1 class="h3 mt-2 mb-1">Posts</h1><p class="text-muted mb-0">All generated, scheduled, and published articles.</p></div><a href="{{ route('autoblog.dashboard') }}" class="btn btn-primary">Generate article</a></div>
<div class="d-flex gap-2 mb-4 flex-wrap">@foreach(['all'=>'All','generated'=>'Generated','scheduled'=>'Scheduled','published'=>'Published'] as $key=>$label)<a href="{{ route('autoblog.posts.index',['status'=>$key]) }}" class="btn btn-sm {{ $status===$key?'btn-primary':'btn-outline-secondary' }}">{{ $label }} <span class="ms-1">{{ $counts[$key] }}</span></a>@endforeach</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Post</th><th>Status</th><th>Destination</th><th>Schedule</th><th>Updated</th><th></th></tr></thead><tbody>
@forelse($posts as $post)<tr><td><div class="fw-semibold">{{ $post->title ?: $post->topic }}</div><div class="small text-muted">{{ Str::limit($post->excerpt,90) }}</div></td><td><span class="badge bg-{{ $post->status==='published'?'success':($post->status==='failed'?'danger':'secondary') }}">{{ str($post->status)->replace('_',' ')->title() }}</span></td><td>{{ $post->destination?->name ?? $post->destination_url ?? 'Draft only' }}</td><td>{{ $post->scheduled_at?->format('M j, Y g:i A') ?? '—' }}</td><td>{{ $post->updated_at->diffForHumans() }}</td><td class="text-end"><a href="{{ route('autoblog.posts.show',$post) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-5">No posts in this category.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $posts->links() }}</div>
@endsection
