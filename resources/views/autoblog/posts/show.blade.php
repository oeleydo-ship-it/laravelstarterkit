@extends('layouts.app')
@section('title', $post->title ?: $post->topic)
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
 <div><a href="{{ route('autoblog.dashboard') }}" class="small text-muted text-decoration-none">&larr; Back to AI Autoblog</a><h1 class="h3 mt-2 mb-1">{{ $post->title ?: $post->topic }}</h1><div class="text-muted">{{ strtoupper($post->provider) }} &middot; {{ $post->destination?->name ?? $post->destination_url ?? 'Draft only' }} &middot; {{ $post->created_at->format('M j, Y g:i A') }}</div></div>
 <span class="badge bg-{{ $post->status==='published'?'success':($post->status==='failed'?'danger':'secondary') }}">{{ str($post->status)->replace('_',' ')->title() }}</span>
</div>
@if($post->last_error)<div class="alert alert-danger">{{ $post->display_error }}</div>@endif
@if($post->content)
<div class="row g-4">
 <div class="col-xl-7"><div class="card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-4"><h5 class="mb-0">Article preview</h5>@if($post->external_url)<a href="{{ $post->external_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">View published</a>@endif</div><article class="autoblog-preview">{!! $post->content !!}</article></div></div></div>
 <div class="col-xl-5"><div class="card"><div class="card-body"><h5 class="mb-3">Edit generated post</h5><form method="POST" action="{{ route('autoblog.posts.update',$post) }}">@csrf @method('PUT')
  <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" value="{{ old('title',$post->title) }}" required></div>
  <div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" value="{{ old('slug',$post->slug) }}" required></div>
  <div class="mb-3"><label class="form-label">Excerpt</label><textarea class="form-control" name="excerpt" rows="3">{{ old('excerpt',$post->excerpt) }}</textarea></div>
  <div class="mb-3"><label class="form-label">HTML content</label><textarea class="form-control font-monospace" name="content" rows="14" required>{{ old('content',$post->content) }}</textarea></div>
  <button class="btn btn-primary">Save changes</button>
 </form></div></div></div>
</div>
@else
<div class="card"><div class="card-body text-center py-5"><h5>Article content is not available yet</h5><p class="text-muted mb-0">Retry generation to create this post.</p></div></div>
@endif
<div class="d-flex gap-2 mt-4">
 @if(($post->destination || $post->destination_url) && $post->content && $post->status!=='published')<form method="POST" action="{{ route('autoblog.posts.publish',$post) }}">@csrf<button class="btn btn-success">Queue publish</button></form>@endif
 @if($post->status==='failed')<form method="POST" action="{{ route('autoblog.posts.retry',$post) }}">@csrf<button class="btn btn-outline-primary">Retry generation</button></form>@endif
 <form method="POST" action="{{ route('autoblog.posts.destroy',$post) }}" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete post</button></form>
</div>
@endsection
