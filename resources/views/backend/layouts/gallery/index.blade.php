@extends('backend.app', ['title' => 'Image Gallery'])
@section('style')
<style>
    .icon-btn:hover {
        background-color: #f0f0f0;
        transform: scale(1.1);
        transition: all 0.2s ease-in-out;
    }
</style>
@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Image Gallery</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Image</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card post-sales-main">
                        <div class="card-body border-0">
                            <div class="form-group">
                                <input type="file" class="dropify form-control" name="images[]" id="images" multiple />
                                <p class="textTransform">Image Size Less than 5MB and Image Type must be jpeg,jpg,png.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12" style="text-align: center">
                    <div id="image_load"></div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')
<script>
    function imagesLoad() {
        $('#image_load').empty();
        $.ajax({
            url: `{{ route('ajax.gallery.all') }}`,
            type: 'GET',
            success: function(resp) {
                let html = '';
                $.each(resp.data, function(key, image) {
                    html += `<div style="height: 150px; width: 150px; object-fit: cover; display: inline-block; margin: 5px">
                                <div class="position-relative">
                                    <img src="` + image.src + `" alt="post image" class="img-fluid img-thumbnail" style="height: 150px; width: 150px; object-fit: cover;">

                                    <!-- Trash Icon -->
                                    <i class="fa fa-trash position-absolute top-0 start-0 d-flex align-items-center justify-content-center bg-white text-danger p-1 rounded-circle icon-btn"
                                    style="cursor: pointer; width: 30px; height: 30px;"
                                    data-name="` + image.name + `"
                                    onclick="deleteImage(event)">
                                    </i>

                                    <!-- Link Icon -->
                                    <a href="` + image.src + `" target="_blank"
                                    class="position-absolute top-0 end-0 d-flex align-items-center justify-content-center bg-white text-info p-1 rounded-circle icon-btn"
                                    style="cursor: pointer; width: 30px; height: 30px;">
                                        <i class="fa-solid fa-link"></i>
                                    </a>
                                </div>
                            </div>`;
                });
                $('#image_load').html(html);
            },
            error: function(resp) {
                $('#image_load').html(resp.message);
            }
        });
    }

    imagesLoad();

    function deleteImage(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this image?")) {
            NProgress.start();
            let name = $(event.target).data('name');
            console.log(name);
            $.ajax({
                url: `{{ route('ajax.gallery.destroy', ':name') }}`.replace(':name', name),
                type: 'GET',
                success: function(resp) {
                    NProgress.done();
                    imagesLoad();
                    toastr.success(resp.message);
                },
                error: function(resp) {
                    NProgress.done();
                    toastr.error(resp.message);
                }
            });
        }
    }

    $('#images').change(function() {
        let data = new FormData();
        let files = $(this)[0].files;

        for (let i = 0; i < files.length; i++) {
            data.append('images[]', files[i]); // append each file with `images[]` name
        }

        NProgress.start();

        $.ajax({
            url: `{{ route('ajax.gallery.store') }}`, // replace with your actual route if needed
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Add CSRF token
            },
            success: function(resp) {
                NProgress.done();
                $('#images').val('');
                imagesLoad();
                toastr.success(resp.message);
            },
            error: function(xhr) {
                NProgress.done();
                let errorMsg = xhr.responseJSON?.message || 'Upload failed';
                toastr.error(errorMsg);
            }
        });
    });
</script>
@endpush