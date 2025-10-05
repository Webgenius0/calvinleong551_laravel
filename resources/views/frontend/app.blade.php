<!DOCTYPE html>
<html lang="en-US">

<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="icon" href="{{ asset(settings()->logo ?? 'default/logo.png') }}" type="image/x-icon">
  <title>{{ $title ?? '' }} | {{ settings()->name ?? config('app.name') }}</title>
  <meta name="description" content="{!! strip_tags($description ?? settings()->description ?? '') !!}">
  <meta name="keywords" content="{!! strip_tags($keywords ?? settings()->keywords ?? '') !!}">
  <meta name="author" content="{{$author ?? settings()->system_name ?? config('app.name') }}">

  <meta property="og:url" content="{{ $url ?? env('APP_URL') }}" />
  <meta property="og:type" content="{{ $type ?? 'website' }}" />
  <meta property="og:title" content="{{ $title ?? settings()->name ?? config('app.name') }}" />
  <meta property="og:description" content="{!! strip_tags($description ?? settings()->description ?? '') !!}" />
  <meta property="og:image" content="{{ $image ?? asset(settings()->thumbnail ?? 'default/logo.png') }}?v={{ time() }}" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="627" />

  <meta name="twitter:card" content="summary" />
  <meta name="twitter:title" content="{{ $title ?? settings()->title ?? config('app.name') }}" />
  <meta name="twitter:description" content="{!! strip_tags($description ?? settings()->description ?? '') !!}" />
  <meta name="twitter:image" content="{{ $image ?? asset(settings()->thumbnail ?? 'default/logo.png') }}" />

  <meta name="csrf-token" content="{{ csrf_token() }}">

  @include('frontend.partials.style')

  <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

  <!-- Preloader -->
  <div id="preloader">
    <div class="outer">
      <!-- Google Chrome -->
      <div class="infinityChrome">
        <div></div>
        <div></div>
        <div></div>
      </div>

      <!-- Safari and others -->
      <div class="infinity">
        <div>
          <span></span>
        </div>
        <div>
          <span></span>
        </div>
        <div>
          <span></span>
        </div>
      </div>
      <!-- Stuff -->
      <svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="goo-outer">
        <defs>
          <filter id="goo">
            <feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur" />
            <feColorMatrix in="blur" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo" />
            <feBlend in="SourceGraphic" in2="goo" />
          </filter>
        </defs>
      </svg>
    </div>
  </div>

  @include('frontend.partials.header')

  @yield('content')

  <!-- Go to top button -->
  <a href="javascript:" id="return-to-top"><i class="fas fa-arrow-up"></i></a>

  @include('frontend.partials.script')

</body>

</html>