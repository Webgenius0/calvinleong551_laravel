@if ($posts->count() > 0)
    <div class="row">
        @foreach ($posts as $post)
            <div class="col-12 col-md-4 p-4 fanimate">
                <div class="features-icon mt-3 mb-3">
                    <img src="{{ $post->thumbnail ? asset($post->thumbnail) : asset('default/logo.svg') }}" alt="Post Image" class="img-fluid" style="width: 50px; max-height: 50px;">
                </div>
                <h4 class="mx-1">{{ $post->name ?? 'Unnamed Project' }}</h4>
                <p class="text-muted mb-3 mx-1">{!! Str::limit($post->content ?? 'No description available', 50) !!}</p>
                <a class="mx-1" href="{{ route('post.show', base64_encode($post->slug)) }}">Read More...</a>
            </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-center">
        {{ $posts->links('pagination::bootstrap-5') }}
    </div>
@else
    <p class="text-center">No posts available.</p>
@endif
