@extends('backend.app', ['title' => 'Boost Plan Update'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Boost Plan Update</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Boost Plan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Update</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">

                    <div class="tab-content">
                        <div class="tab-pane active show" id="editProfile">
                            <div class="card">
                                <div class="card-body border-0">
                                    <form class="form form-horizontal" method="post" action="{{ route('admin.boost-plan.update', $data->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('POST')
                                        <div class="row mb-4">

                                            <div class="form-group">
                                                <label for="username" class="form-label">Name:</label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter here name" id="" value="{{ $data->name }}">
                                                @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label for="username" class="form-label">Duration:</label>
                                                <input type="number" class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Enter here duration" id="" value="{{ $data->duration }}">
                                                @error('duration')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="username" class="form-label">Price:</label>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" placeholder="Enter here price" id="" value="{{ $data->price }}">
                                                @error('price')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="is_default" class="form-label">Boosting Type:</label>
                                                <select class="form-control @error('is_default') is-invalid @enderror" name="is_default" id="is_default">
                                                    <option value="1" {{ $data->is_default == 1 ? 'selected' : '' }}>Regular Boost</option>
                                                    <option value="0" {{ $data->is_default == 0 ? 'selected' : '' }}>Custom Boost</option>
                                                </select>
                                                @error('is_default')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>




                                            <div class="form-group">
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