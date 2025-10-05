@extends('backend.app', ['title' => 'Update Subcategory'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Subcategory</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Subcategory</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Update</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body border-0">
                            <form method="post" action="{{ route('admin.brand.update', $subcategory->id) }}" enctype="multipart/form-data">
                                @csrf
                           

                                {{-- Subcategory Dropdown --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Subcategory</label>
                                    <select name="subcategory_id" class="form-control">
                                        <option value="">-- Select Subcategory --</option>
                                        @foreach($allSubcategories as $sub)
                                        <option value="{{ $sub->id }}" {{ $subcategory->id == $sub->id ? 'selected' : '' }}>
                                            {{ $sub->name ?? '' }}
                                        </option>
                                        @endforeach
                                    </select>

                                    @error('subcategory_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                               
                                {{-- Brands --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Brands</label>
                                    <div id="brand-wrapper">
                                        @foreach($subcategory->subcategoryBrand as $brand)
                                        <div class="d-flex mb-2 brand-item">
                                            <input type="text" name="brands[]" class="form-control" value="{{ $brand->brand_name }}">
                                            <button type="button" class="btn btn-{{ $loop->last ? 'success add-brand' : 'danger remove-brand' }} ms-2">
                                                {{ $loop->last ? '+' : '-' }}
                                            </button>
                                        </div>
                                        @endforeach
                                        @if($subcategory->subcategoryBrand->isEmpty())
                                        <div class="d-flex mb-2 brand-item">
                                            <input type="text" name="brands[]" class="form-control" placeholder="Brand Name">
                                            <button type="button" class="btn btn-success ms-2 add-brand">+</button>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Sizes --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Sizes</label>
                                    <div id="size-wrapper">
                                        @foreach($subcategory->subcategorySize as $size)
                                        <div class="d-flex mb-2 size-item">
                                            <input type="text" name="sizes[]" class="form-control" value="{{ $size->size }}">
                                            <button type="button" class="btn btn-{{ $loop->last ? 'success add-size' : 'danger remove-size' }} ms-2">
                                                {{ $loop->last ? '+' : '-' }}
                                            </button>
                                        </div>
                                        @endforeach
                                        @if($subcategory->subcategorySize->isEmpty())
                                        <div class="d-flex mb-2 size-item">
                                            <input type="text" name="sizes[]" class="form-control" placeholder="Size Name">
                                            <button type="button" class="btn btn-success ms-2 add-size">+</button>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Materials --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Materials</label>
                                    <div id="material-wrapper">
                                        @foreach($subcategory->subcategoryMaterial as $material)
                                        <div class="d-flex mb-2 material-item">
                                            <input type="text" name="materials[]" class="form-control" value="{{ $material->material_name }}">
                                            <button type="button" class="btn btn-{{ $loop->last ? 'success add-material' : 'danger remove-material' }} ms-2">
                                                {{ $loop->last ? '+' : '-' }}
                                            </button>
                                        </div>
                                        @endforeach
                                        @if($subcategory->subcategoryMaterial->isEmpty())
                                        <div class="d-flex mb-2 material-item">
                                            <input type="text" name="materials[]" class="form-control" placeholder="Material Name">
                                            <button type="button" class="btn btn-success ms-2 add-material">+</button>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Conditions --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Conditions</label>
                                    <div id="condition-wrapper">
                                        @foreach($subcategory->subcategoryCondition as $condition)
                                        <div class="d-flex mb-2 condition-item">
                                            <input type="text" name="condition_title[]" class="form-control me-2" value="{{ $condition->title }}" placeholder="Condition Title">
                                            <input type="text" name="condition_subtitle[]" class="form-control" value="{{ $condition->condition }}" placeholder="Condition Subtitle">
                                            <button type="button" class="btn btn-{{ $loop->last ? 'success add-condition' : 'danger remove-condition' }} ms-2">
                                                {{ $loop->last ? '+' : '-' }}
                                            </button>
                                        </div>
                                        @endforeach
                                        @if($subcategory->subcategoryCondition->isEmpty())
                                        <div class="d-flex mb-2 condition-item">
                                            <input type="text" name="condition_title[]" class="form-control me-2" placeholder="Condition Title">
                                            <input type="text" name="condition_subtitle[]" class="form-control" placeholder="Condition Subtitle">
                                            <button type="button" class="btn btn-success ms-2 add-condition">+</button>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Colors --}}
                                <div class="form-group mb-3">
                                    <label class="form-label">Colors</label>
                                    <div id="color-wrapper">
                                        @foreach($subcategory->subcategoryColor as $color)
                                        <div class="d-flex mb-2 color-item align-items-center">
                                            <input type="color" name="color_code[]" class="form-control form-control-color me-2" value="{{ $color->color_code }}">
                                            <input type="text" name="color_name[]" class="form-control" value="{{ $color->color_name }}">
                                            <button type="button" class="btn btn-{{ $loop->last ? 'success add-color' : 'danger remove-color' }} ms-2">
                                                {{ $loop->last ? '+' : '-' }}
                                            </button>
                                        </div>
                                        @endforeach
                                        @if($subcategory->subcategoryColor->isEmpty())
                                        <div class="d-flex mb-2 color-item align-items-center">
                                            <input type="color" name="color_code[]" class="form-control form-control-color me-2" value="#000000">
                                            <input type="text" name="color_name[]" class="form-control" placeholder="Color Name">
                                            <button type="button" class="btn btn-success ms-2 add-color">+</button>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <div class="form-group">
                                    <button class="btn btn-primary" type="submit">Update</button>
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

        function dynamicAdd(wrapper, itemHtml) {
            $(wrapper + ' .add-btn').removeClass('btn-success add-btn').addClass('btn-danger remove-btn').text('-');
            $(wrapper).append(itemHtml);
        }

        // --- Brand ---
        $(document).on('click', '.add-brand', function() {
            $('#brand-wrapper .add-brand').removeClass('btn-success add-brand').addClass('btn-danger remove-brand').text('-');
            $('#brand-wrapper').append(`<div class="d-flex mb-2 brand-item">
            <input type="text" name="brands[]" class="form-control" placeholder="Brand Name">
            <button type="button" class="btn btn-success ms-2 add-brand">+</button>
        </div>`);
        });
        $(document).on('click', '.remove-brand', function() {
            $(this).closest('.brand-item').remove();
        });

        // --- Size ---
        $(document).on('click', '.add-size', function() {
            $('#size-wrapper .add-size').removeClass('btn-success add-size').addClass('btn-danger remove-size').text('-');
            $('#size-wrapper').append(`<div class="d-flex mb-2 size-item">
            <input type="text" name="sizes[]" class="form-control" placeholder="Size Name">
            <button type="button" class="btn btn-success ms-2 add-size">+</button>
        </div>`);
        });
        $(document).on('click', '.remove-size', function() {
            $(this).closest('.size-item').remove();
        });

        // --- Material ---
        $(document).on('click', '.add-material', function() {
            $('#material-wrapper .add-material').removeClass('btn-success add-material').addClass('btn-danger remove-material').text('-');
            $('#material-wrapper').append(`<div class="d-flex mb-2 material-item">
            <input type="text" name="materials[]" class="form-control" placeholder="Material Name">
            <button type="button" class="btn btn-success ms-2 add-material">+</button>
        </div>`);
        });
        $(document).on('click', '.remove-material', function() {
            $(this).closest('.material-item').remove();
        });

        // --- Condition ---
        $(document).on('click', '.add-condition', function() {
            $('#condition-wrapper .add-condition').removeClass('btn-success add-condition').addClass('btn-danger remove-condition').text('-');
            $('#condition-wrapper').append(`<div class="d-flex mb-2 condition-item">
            <input type="text" name="condition_title[]" class="form-control me-2" placeholder="Condition Title">
            <input type="text" name="condition_subtitle[]" class="form-control" placeholder="Condition Subtitle">
            <button type="button" class="btn btn-success ms-2 add-condition">+</button>
        </div>`);
        });
        $(document).on('click', '.remove-condition', function() {
            $(this).closest('.condition-item').remove();
        });

        // --- Color ---
        $(document).on('click', '.add-color', function() {
            $('#color-wrapper .add-color').removeClass('btn-success add-color').addClass('btn-danger remove-color').text('-');
            $('#color-wrapper').append(`<div class="d-flex mb-2 color-item align-items-center">
            <input type="color" name="color_code[]" class="form-control form-control-color me-2" value="#000000">
            <input type="text" name="color_name[]" class="form-control" placeholder="Color Name">
            <button type="button" class="btn btn-success ms-2 add-color">+</button>
        </div>`);
        });
        $(document).on('click', '.remove-color', function() {
            $(this).closest('.color-item').remove();
        });

    });
</script>
@endpush