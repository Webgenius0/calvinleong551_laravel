<section id="prices" style="background-color: #f8fafc;">

    <div class="container">

        <!-- section title -->
        <h2 class="section-title wow fadeIn">Pricing Plans</h2>

        <div class="spacer" data-height="60"></div>

        <div class="row">

            @forelse ($products as $product)
            <div class="col-md-6 col-lg-4 my-3 d-flex">
                <div class="price-item bg-white rounded shadow-dark text-center py-4 flex-fill" style="position: relative;">
                    <img src="{{ $product->thumbnail && file_exists(public_path($product->thumbnail)) ? asset($product->thumbnail) : asset('default/logo.svg') }}" alt="Premium" class="img-fluid mb-3" style="width:80px; height:80px;" />
                    <h2 class="plan">{{ $product->category->name }}</h2>
                    <p>{{ $product->title }}</p>
                    @if ($product->description)
                        {!! $product->description !!}
                    @endif
                    <h3 class="price">
                        <em>${{ number_format($product->price, 2) }}</em>
                    </h3>
                    <div style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);">
                        <a href="#" class="btn btn-default mt-3">Get Started</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center">
                <p>No products available at the moment.</p>
            </div>
            @endforelse

        </div>

    </div>

</section>
