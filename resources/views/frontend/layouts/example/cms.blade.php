@php
use \Illuminate\Support\Str;
use App\Enums\PageEnum;
use App\Enums\SectionEnum;
$cms_banner = $cms['home']->firstWhere('section', SectionEnum::BANNER);
$cms_banners = $cms['home']->where('section', SectionEnum::BANNERS)->values();
@endphp
<section class="{{ isset($cms_banner->is_display) && $cms_banner->is_display == false ? 'd-none' : '' }}">
    <h1 class="text-shadow text-dark">{{ $cms_banner->title ?? 'Album example' }}</h1>
    <h6 class="mt-3">{!! $cms_banner->description ?? 'Something short and leading about the collection below—its contents, the creator, etc. Make it short and sweet, but not too short so folks don\'t simply skip over it entirely.' !!}</h6>
    <a href="{{ $cms_banner->btn_link ?? '#' }}" class="btn btn-pill btn-primary btn-w-md py-2 me-2 mb-1">{{ $cms_banner->btn_text ?? 'Main call to action' }}<i class="fe fe-activity ms-2"></i></a>
    <img alt="" class="logo-2" src="{{ asset($cms_banner->image ?? 'frontend/images/landing/market.png') }}">

    @if ($cms_banners->count() > 0)
    @foreach ($cms_banners as $item)
    <div class="col-lg-3 col-sm-6 d-flex align-items-center">
        <div class="card shadow-sm border-0 overflow-hidden w-100">
            <div class="card-body d-flex align-items-center p-3">
                <a href="#" class="d-block w-100 h-100">
                    <img class="img-fluid mx-auto d-block" src="{{ $item->image ?? 'default/logo.svg' }}" alt="">
                </a>
                <div class="text-center">
                    <h5 class="mt-3">{{ $item->metadata['rating'] ?? '5' }}</h5>
                    <h5 class="mt-3">{{ $item->title ?? 'Example headline.' }}</h5>
                    <p class="fs-13">{!! $item->description ?? 'Some representative placeholder content for the first slide of the carousel.' !!}</p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @else
    @for ($i = 1; $i <= 4; $i++)
        <div class="col-lg-3 col-sm-6 d-flex align-items-center">
        <div class="card shadow-sm border-0 overflow-hidden w-100">
            <div class="card-body d-flex align-items-center p-3">
                <a href="#" class="d-block w-100 h-100">
                    <img class="img-fluid mx-auto d-block" src="{{ asset('default') }}/logo.svg" alt="">
                </a>
                <div class="text-center">
                    <h5 class="mt-3">{{ $i }}. Example headline</h5>
                    <p class="fs-13">Some representative placeholder content for slide {{ $i }} of the carousel.</p>
                </div>
            </div>
        </div>
        </div>
        @endfor
        @endif
</section>