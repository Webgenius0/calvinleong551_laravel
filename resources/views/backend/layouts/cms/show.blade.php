@php
$url = 'admin.cms.'.$page.'.'.$section;
@endphp

@extends('backend.app', ['title' => $page . ' - ' . $section])

@push('styles')
<style>
    /* General Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f7fc;
        color: #333;
    }

    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, #cfa3cfff, #9fa3aaff);
        padding: 20px 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        text-transform: capitalize;
        letter-spacing: 0.5px;
    }

    .breadcrumb {
        background: none;
        padding: 0;
    }

    .breadcrumb-item a {
        color: #e0e7ff;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .breadcrumb-item a:hover {
        color: #ffffff;
    }

    .breadcrumb-item.active {
        color: #ffffff;
        font-weight: 500;
    }

    /* Card Styling */
    .post-sales-main {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .post-sales-main:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: #ffffff;
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
    }

    .card-options .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .card-options .btn:hover {
        transform: scale(1.05);
    }

    /* Table Styling */
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
    }

    .table th,
    .table td {
        padding: 15px;
        vertical-align: middle;
        border: none;
    }

    .table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 600;
        text-align: left;
    }

    .table td {
        background: #ffffff;
        color: #4b5563;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9fafb;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f5f9;
        transition: background-color 0.2s ease;
    }

    /* Image Preview */
    .img-preview {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .img-preview:hover {
        transform: scale(1.05);
    }

    /* Custom Button */
    .btn-custom {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-custom:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-title {
            font-size: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
        }

        .table th,
        .table td {
            padding: 10px;
        }

        .img-preview {
            width: 80px;
            height: 80px;
        }
    }

    @media (max-width: 576px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .breadcrumb {
            margin-top: 10px;
        }

        .table {
            font-size: 0.9rem;
        }
    }
</style>
@endpush
@section('content')
<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">
        <!-- CONTAINER -->
        <div class="main-container container-fluid">
            <!-- Page Header -->
            <div class="page-header d-flex align-items-center justify-content-between">
                <h1 class="page-title">CMS: {{ ucwords(str_replace('_', ' ', $page ?? '')) }} - {{ ucwords(str_replace('_', ' ', $section ?? '')) }}</h1>
                <ol class="breadcrumb ms-auto">
                    <li class="breadcrumb-item"><a href="#">CMS</a></li>
                    <li class="breadcrumb-item">{{ ucwords(str_replace('_', ' ', $page ?? '')) }}</li>
                    <li class="breadcrumb-item">{{ ucwords(str_replace('_', ' ', $section ?? '')) }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Show</li>
                </ol>
            </div>

            <!-- Content Card -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card post-sales-main shadow-sm">
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Content: {{ ucwords(str_replace('_', ' ', $page ?? '')) }}</h3>
                            <a href="javascript:window.history.back()" class="btn btn-sm btn-outline-primary">Back</a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped table-hover">
                                <tr>
                                    <th>Page</th>
                                    <td>{{ Str::limit($data->page ?? 'N/A', 20) }}</td>
                                </tr>
                                <tr>
                                    <th>Section</th>
                                    <td>{{ Str::limit($data->section ?? 'N/A', 20) }}</td>
                                </tr>
                                <tr>
                                    <th>Slug</th>
                                    <td>{{ Str::limit($data->slug ?? 'N/A', 20) }}</td>
                                </tr>
                                <tr>
                                    <th>Title</th>
                                    <td>{{ $data->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Sub Title</th>
                                    <td>{{ $data->sub_title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Background Image</th>
                                    <td>
                                        <img src="{{ asset(!empty($data->image) && file_exists(public_path($data->image)) ? $data->image : 'default/logo.png') }}" class="img-preview" alt="Background Image" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Button</th>
                                    <td>
                                        <button onclick="window.open('{{ $data->btn_link ?? '#'}}')" class="btn btn-custom btn-outline-primary">{{ $data->btn_text ?? 'No Button Text' }}</button>
                                    </td>
                                </tr>

                            </table>
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
<script>
    // Add animation for table rows on load
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelectorAll('.table tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
@endpush