@php
$url = 'admin.cms.'.$page.'.'.$section;

$hasImage = in_array('image', $components);
$hasBg = in_array('bg', $components);
$hasVideo = in_array('video', $components);
$hasBoth = $hasImage && $hasBg;
@endphp

@extends('backend.app', ['title' => 'Create Banner'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">CMS : {{ ucwords(str_replace('_', ' ', $page ?? '')) }} Page {{ ucfirst(str_replace('_', ' ', $section ?? '')) }} Section Create.</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">CMS</li>
                        <li class="breadcrumb-item">{{ ucwords(str_replace('_', ' ', $page ?? '')) }}</li>
                        <li class="breadcrumb-item">{{ ucwords(str_replace('_', ' ', $section ?? '')) }}</li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">

                    <div class="tab-content">
                        <div class="tab-pane active show" id="editProfile">
                            <div class="card">
                                <div class="card-body border-0">
                                    <form class="form form-horizontal" method="POST" action="{{ route($url.'.store') }}" enctype="multipart/form-data">
                                        @csrf

                                        <div class="row {{ in_array('metadata', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="rating" class="form-label">Rating:</label>
                                                    <input type="number" class="form-control @error('rating') is-invalid @enderror" name="rating" placeholder="Enter here rating" id="rating" value="{{ old('rating') }}" min="1" max="5">
                                                    @error('rating')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row {{ in_array('title', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <x-form.text name="title" label="Title" placeholder="Enter here title" :value="old('title') ?? ''" />
                                            </div>
                                        </div>

                                        <div class="row {{ in_array('sub_title', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <x-form.text name="sub_title" label="Sub Title" placeholder="Enter here sub title" :value="old('sub_title') ?? ''" />
                                            </div>
                                        </div>

                                        <div class="row {{ in_array('description_title', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <x-form.text name="description_title" label="Description Title" placeholder="Enter here description title" :value="old('description_title') ?? ''" />
                                            </div>
                                        </div>

                                        <div class="row {{ in_array('description', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <x-form.textarea name="description" label="Description" placeholder="Enter here description" :value="old('description') ?? ''" />
                                            </div>
                                        </div>

                                        <div class="row {{ in_array('sub_description', $components) ? '' : 'd-none' }}">
                                            <div class="col-md-12">
                                                <x-form.textarea name="sub_description" label="Sub Description" placeholder="Enter here sub description" :value="old('sub_description') ?? ''" />
                                            </div>
                                        </div>

                                        @if($hasImage || $hasBg ||$hasVideo)
                                        <div class="row">
                                            @if($hasImage)
                                            <div class="{{ $hasBoth ? 'col-md-6' : 'col-md-12' }}">
                                                <x-form.file name="image" label="Image">
                                                    <p class="textTransform">Image Size Less than 5MB and Image Type must be jpeg, jpg, png.</p>
                                                </x-form.file>
                                            </div>
                                            @endif

                                            @if($hasBg)
                                            <div class="{{ $hasBoth ? 'col-md-6' : 'col-md-12' }}">
                                                <x-form.file name="bg" label="Background">
                                                    <p class="textTransform">Image Size Less than 5MB and Image Type must be jpeg, jpg, png.</p>
                                                </x-form.file>
                                            </div>
                                            @endif


                                            @if($hasVideo)
                                            <div class="{{ ($hasImage || $hasBg) ? 'col-md-4' : 'col-md-12' }}">
                                                <x-form.file name="video" label="Video">
                                                    <p class="textTransform">Video Size must be less than 10MB. Allowed formats: mp4, avi, mov.</p>
                                                </x-form.file>
                                            </div>
                                            @endif

                                        </div>
                                        @endif

                                        <div class="row mt-4">
                                            <div class="col-md-12 text-center">
                                                <button class="submit btn btn-primary" type="submit">Submit</button>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection

@push('scripts')

@endpush