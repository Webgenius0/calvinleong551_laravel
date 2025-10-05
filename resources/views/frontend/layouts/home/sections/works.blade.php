<section id="works">

    <div class="container">

        <!-- section title -->
        <h2 class="section-title wow fadeInUp">Recent works</h2>

        <div class="spacer" data-height="60"></div>

        <!-- portfolio filter (desktop) -->
        <ul class="portfolio-filter list-inline wow fadeInUp">
            <li class="current list-inline-item" data-filter="*">All</li>
            @if($types && count($types) > 0)
            @foreach($types as $type)
            <li class="list-inline-item" data-filter=".{{ $type->slug ?? '' }}">{{ $type->name ?? '' }}</li>
            @endforeach
            @endif
        </ul>

        <!-- portfolio filter (mobile) -->
        <div class="pf-filter-wrapper">
            <select class="portfolio-filter-mobile">
                <option value="*">All</option>
                @if($types && count($types) > 0)
                @foreach($types as $type)
                <option value=".{{ $type->slug ?? '' }}">{{ $type->name ?? '' }}</option>
                @endforeach
                @endif
            </select>
        </div>

        <!-- portolio wrapper -->
        <div class="row portfolio-wrapper">

            @forelse($projects as $project)
            <div class="col-md-4 col-sm-6 grid-item {{ $project->type->slug ?? '' }}">
                <a href="{{ json_decode($project->metadata)->url ?? '#' }}" target="_blank">
                    <div class="portfolio-item rounded shadow-dark">
                        <div class="details">
                            <span class="term">{{ $project->type->name ?? '' }}</span>
                            <h4 class="title">{{ $project->name ?? '' }}</h4>
                            <span class="more-button"><i class="icon-link"></i></span>
                        </div>
                        <div class="thumb">
                            <img src="{{ asset($project->thumbnail && file_exists(public_path($project->thumbnail)) ? $project->thumbnail : 'default/logo.svg') }}" alt="Portfolio-title" style="width:330px; height:267px;" />
                            <div class="mask"></div>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center">
                <p class="text-muted">No works available.</p>
            </div>
            @endforelse

        </div>

        <!-- more button -->
        <div class="load-more text-center mt-4">
            <a href="javascript:" class="btn btn-default"><i class="fas fa-spinner"></i> Load more</a>
            <!-- numbered pagination (hidden for infinite scroll) -->
            <ul class="portfolio-pagination list-inline d-none">
                <li class="list-inline-item">1</li>
                <li class="list-inline-item"><a href="works-2.html">2</a></li>
            </ul>
        </div>

    </div>

</section>