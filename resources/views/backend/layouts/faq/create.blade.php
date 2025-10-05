@extends('backend.app', ['title' => 'Create Faq'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">FAQ</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Faq</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card post-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">Create Faq</h3>
                            <div class="card-options">
                                <a href="javascript:window.history.back()" class="btn btn-sm btn-primary">Back</a>
                            </div>
                        </div>
                        <div class="card-body border-0">
                            <form class="form form-horizontal" method="POST" action="{{ route('admin.faq.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-4">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="question" class="form-label">Question:</label>
                                                <input type="text" class="form-control @error('question') is-invalid @enderror" name="question" placeholder="Enter here question" id="question" value="{{ old('question') ?? '' }}">
                                                @error('question')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="answer" class="form-label">Answer:</label>
                                                <textarea class="summernote form-control @error('answer') is-invalid @enderror" name="answer" id="answer" placeholder="Enter here answer" rows="3">{{ old('answer') ?? '' }}</textarea>
                                                @error('answer')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
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
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 with tags functionality
        $('#category').select2({
            placeholder: "Select or type a new position",
            allowClear: true,
            width: '100%',
            tags: true,
            createTag: function(params) {
                var term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                // Check if the term already exists
                var existsInOptions = false;
                $('#category option').each(function() {
                    if ($(this).text().toLowerCase() === term.toLowerCase()) {
                        existsInOptions = true;
                        return false;
                    }
                });

                if (existsInOptions) {
                    return null;
                }

                return {
                    id: term,
                    text: term + ' (new)',
                    newTag: true
                };
            },
            templateResult: function(data) {
                if (data.newTag) {
                    return $('<span><em>Create:</em> <strong>' + data.text.replace(' (new)', '') + '</strong></span>');
                }
                return data.text;
            },
            templateSelection: function(data) {
                if (data.newTag) {
                    return data.text.replace(' (new)', '');
                }
                return data.text;
            }
        });

        // Restore old value if validation fails (including custom values)
        @if(old('category'))
        var oldValue = "{{ old('category') }}";
        // Check if old value exists in current options
        var existsInOptions = false;
        $('#category option').each(function() {
            if ($(this).val() === oldValue) {
                existsInOptions = true;
                return false;
            }
        });

        // If old value doesn't exist in options, add it as a new option
        if (!existsInOptions && oldValue !== '') {
            var newOption = new Option(oldValue, oldValue, true, true);
            $('#category').append(newOption);
        }

        $('#category').val(oldValue).trigger('change');
        @endif
    });
</script>
@endpush