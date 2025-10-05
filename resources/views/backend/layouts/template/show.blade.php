@extends('backend.app', ['title' => 'Show Post'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Post</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Post</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Show</li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card post-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">{{ Str::limit($post->title, 50) }}</h3>
                            <div class="card-options">
                                <a href="javascript:window.history.back()" class="btn btn-sm btn-primary">Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th>Thumbnail</th>
                                    <td>
                                        <a href="{{ asset($post->thumb ?? 'default/logo.png') }}" target="_blank"><img src="{{ asset($post->thumb ?? 'default/logo.png') }}" alt="" width="50" height="50" class="img-fluid"></a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Images</th>
                                    <td>
                                        @if($post->images)
                                        @foreach ($post->images as $image)
                                        <a href="{{ asset($image->path ?? 'default/logo.png') }}" target="_blank"><img src="{{ asset($image->path ?? 'default/logo.png') }}" class="img-fluid" alt="post image" width="50" height="50"></a>
                                        @endforeach
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Title</th>
                                    <td>{{ $post->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Slug</th>
                                    <td>{{ $post->slug ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Author</th>
                                    <td><a href="{{ route('admin.users.show', $post->user->id) }}">{{ $post->user->name }}</a></td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td> <a href="{{ route('admin.category.show', $post->category->id) }}">{{ $post->category->name }}</a></td>
                                </tr>
                                <tr>
                                    <th>Subcategory</th>
                                    <td> <a href="{{ route('admin.subcategory.show', $post->subcategory->id) }}">{{ $post->subcategory->name }}</a></td>
                                </tr>
                                <tr>
                                    <th>Content</th>
                                    <td>{!! $post->content ?? 'N/A' !!}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $post->created_at ? $post->created_at : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $post->updated_at ? $post->updated_at : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm(`{{ $post->id }}`)">Delete</button>
                                        <button class="btn btn-sm btn-primary" onclick="goToEdit(`{{ $post->id }}`)">Edit</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div><!-- COL END -->
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')
<script>
    // delete Confirm
    function showDeleteConfirm(id) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to delete this record?',
            text: 'If you delete this, it will be gone forever.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
            if (result.isConfirmed) {
                deleteItem(id);
            }
        });
    }

    // Delete Button
    function deleteItem(id) {
        NProgress.start();
        let url = "{{ route('admin.post.destroy', ':id') }}";
        let csrfToken = '{{ csrf_token() }}';
        $.ajax({
            type: "DELETE",
            url: url.replace(':id', id),
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(resp) {
                NProgress.done();
                toastr.success(resp.message);
                window.location.href = "{{ route('admin.post.index') }}";
            },
            error: function(error) {
                NProgress.done();
                toastr.error(error.message);
            }
        });
    }

    //edit
    function goToEdit(id) {
        let url = "{{ route('admin.post.edit', ':id') }}";
        window.location.href = url.replace(':id', id);
    }
</script>
@endpush