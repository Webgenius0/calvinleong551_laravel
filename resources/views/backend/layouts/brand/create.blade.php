@extends('backend.app', ['title' => 'Create Brand'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Brand</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Brand</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body border-0">
                            <form class="form form-horizontal" method="post" action="{{ route('admin.brand.store') }}" enctype="multipart/form-data">
                                @csrf

                                {{-- Category Name --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Subcategory</label>
                                    <select class="form-control" name="subcategory_id">
                                        <option value="">Select Subcategory</option>
                                        @foreach($subcategory as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('subcategory_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>


                                {{-- Brand --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Brands</label>
                                    <div id="brand-wrapper">
                                        <div class="d-flex mb-2 brand-item">
                                            <input type="text" name="brands[]" class="form-control" placeholder="Brand Name">
                                            <button type="button" class="btn btn-success ms-2 add-brand">+</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Size --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Sizes</label>
                                    <div id="size-wrapper">
                                        <div class="d-flex mb-2 size-item">
                                            <input type="text" name="sizes[]" class="form-control" placeholder="Size Name">
                                            <button type="button" class="btn btn-success ms-2 add-size">+</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Material --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Materials</label>
                                    <div id="material-wrapper">
                                        <div class="d-flex mb-2 material-item">
                                            <input type="text" name="materials[]" class="form-control" placeholder="Material Name">
                                            <button type="button" class="btn btn-success ms-2 add-material">+</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Condition --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Conditions</label>
                                    <div id="condition-wrapper">
                                        <div class="d-flex mb-2 condition-item">
                                            <input type="text" name="condition_title[]" class="form-control me-2" placeholder="Condition Title">
                                            <input type="text" name="condition_subtitle[]" class="form-control" placeholder="Condition Subtitle">
                                            <button type="button" class="btn btn-success ms-2 add-condition">+</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Colors --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Colors</label>
                                    <div id="color-wrapper">
                                        <div class="d-flex mb-2 color-item align-items-center">
                                            <input type="color" name="color_code[]" class="form-control form-control-color me-2" value="#000000">
                                            <input type="text" name="color_name[]" class="form-control" placeholder="Color Name">
                                            <button type="button" class="btn btn-success ms-2 add-color">+</button>
                                        </div>
                                    </div>
                                </div>

                               

                                {{-- Submit --}}
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {

        // Brand
        $(document).on('click', '.add-brand', function() {
            $('#brand-wrapper').append(`
            <div class="d-flex mb-2 brand-item">
                <input type="text" name="brands[]" class="form-control" placeholder="Brand Name">
                <button type="button" class="btn btn-danger ms-2 remove-brand">-</button>
            </div>`);
        });
        $(document).on('click', '.remove-brand', function() {
            $(this).closest('.brand-item').remove();
        });

        // Size
        $(document).on('click', '.add-size', function() {
            $('#size-wrapper').append(`
            <div class="d-flex mb-2 size-item">
                <input type="text" name="sizes[]" class="form-control" placeholder="Size Name">
                <button type="button" class="btn btn-danger ms-2 remove-size">-</button>
            </div>`);
        });
        $(document).on('click', '.remove-size', function() {
            $(this).closest('.size-item').remove();
        });

        // Material
        $(document).on('click', '.add-material', function() {
            $('#material-wrapper').append(`
            <div class="d-flex mb-2 material-item">
                <input type="text" name="materials[]" class="form-control" placeholder="Material Name">
                <button type="button" class="btn btn-danger ms-2 remove-material">-</button>
            </div>`);
        });
        $(document).on('click', '.remove-material', function() {
            $(this).closest('.material-item').remove();
        });

        // Condition
        $(document).on('click', '.add-condition', function() {
            $('#condition-wrapper').append(`
            <div class="d-flex mb-2 condition-item">
                <input type="text" name="condition_title[]" class="form-control me-2" placeholder="Condition Title">
                <input type="text" name="condition_subtitle[]" class="form-control" placeholder="Condition Subtitle">
                <button type="button" class="btn btn-danger ms-2 remove-condition">-</button>
            </div>`);
        });
        $(document).on('click', '.remove-condition', function() {
            $(this).closest('.condition-item').remove();
        });

        // Color
        $(document).on('click', '.add-color', function() {
            $('#color-wrapper').append(`
            <div class="d-flex mb-2 color-item align-items-center">
                <input type="color" name="color_code[]" class="form-control form-control-color me-2" value="#000000">
                <input type="text" name="color_name[]" class="form-control" placeholder="Color Name">
                <button type="button" class="btn btn-danger ms-2 remove-color">-</button>
            </div>`);
        });
        $(document).on('click', '.remove-color', function() {
            $(this).closest('.color-item').remove();
        });

    });
</script>
@endpush