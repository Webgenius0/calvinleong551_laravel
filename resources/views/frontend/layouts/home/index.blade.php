@php
use \Illuminate\Support\Str;
use App\Enums\PageEnum;
use App\Enums\SectionEnum;
$cms_example    = $cms['home']->firstWhere('section', SectionEnum::EXAMPLE);
$cms_examples   = $cms['home']->where('section', SectionEnum::EXAMPLES)->values();
$cms_intro      = $cms['home']->firstWhere('section', SectionEnum::INTRO);
$cms_about      = $cms['home']->firstWhere('section', SectionEnum::ABOUT);
@endphp

@extends('frontend.app', ['title' => 'landing page'])
@section('content')
<!-- main layout -->
<main class="content">

    <!-- section intro -->
    @include('frontend.layouts.home.sections.intro', ['cms_intro' => $cms_intro])

    <!-- section about -->
    @include('frontend.layouts.home.sections.about', ['cms_about' => $cms_about])

    <!-- section services -->
    @include('frontend.layouts.home.sections.services')

    <!-- section experience -->
    @include('frontend.layouts.home.sections.experience')

    <!-- section works -->
    @include('frontend.layouts.home.sections.works', ['types' => $types, 'projects' => $projects])

    <!-- section prices -->
    @include('frontend.layouts.home.sections.prices', ['products' => $products])

    <!-- section testimonials -->
    @include('frontend.layouts.home.sections.testimonials')

    <!-- section blog -->
    @include('frontend.layouts.home.sections.blog', ['posts' => $posts])

    <!-- section contact -->
    @include('frontend.layouts.home.sections.contact')

    <div class="spacer" data-height="96"></div>

</main>
@endsection