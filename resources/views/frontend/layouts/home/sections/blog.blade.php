<section id="blog">

    <div class="container">

        <!-- section title -->
        <h2 class="section-title wow fadeInUp">Latest Posts</h2>

        <div class="spacer" data-height="60"></div>

        <div class="row blog-wrapper">

            @forelse($posts as $post)
            <div class="col-md-4">
                <div class="blog-item rounded bg-white shadow-dark wow fadeIn">
                    <div class="thumb">
                        <a href="#"><span class="category">{{ $post->category->name }}</span></a>
                        <a href="#"><img src="{{ asset($post->thumbnail ?? 'default/logo.svg') }}" alt="blog-title" style="width:330px; height:215px;" /></a>
                    </div>
                    <div class="details">
                        <h4 class="my-0 title"><a href="#">{{ $post->title ?? 'Unnamed Project' }}</a></h4>
                        <ul class="list-inline meta mb-0 mt-2">
                            <li class="list-inline-item">{{ $post->created_at->format('M d, Y') }}</li>
                            <li class="list-inline-item">{{ $post->user->name }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center">
                <p class="text-muted">No posts available.</p>
            </div>
            @endforelse

        </div>

    </div>

</section>