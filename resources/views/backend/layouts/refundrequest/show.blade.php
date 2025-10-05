@extends('backend.app', ['title' => 'Refund Details'])

@section('content')
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header d-flex align-items-center justify-content-between">
                <h1 class="page-title">Refund Request Details</h1>
                <div class="pageheader-btn">
                    <a href="javascript:window.history.back()" class="btn btn-primary btn-icon">
                        <i class="fe fe-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Images -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Product Image</h5>
                        </div>
                        <div class="card-body text-center">
                            <a href="{{ asset($refund->orderItem->image ?? 'default/logo.png') }}" target="_blank">
                                <img src="{{ asset($refund->orderItem->image ?? 'default/logo.png') }}" 
                                     alt="Product Image" 
                                     class="img-fluid rounded mb-2 shadow-sm" 
                                     style="max-height: 200px; object-fit: contain;">
                            </a>
                            <p class="text-muted small mb-0">Product</p>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Refund Issue Image</h5>
                        </div>
                        <div class="card-body text-center">
                            <a href="{{ asset($refund->image ?? 'default/logo.png') }}" target="_blank">
                                <img src="{{ asset($refund->image ?? 'default/logo.png') }}" 
                                     alt="Refund Issue Image" 
                                     class="img-fluid rounded shadow-sm" 
                                     style="max-height: 200px; object-fit: contain;">
                            </a>
                            <p class="text-muted small mb-0">Provided by Buyer</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Details -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Refund Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th width="30%">Reason</th>
                                        <td>{{ $refund->reason ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order ID</th>
                                        <td><span class="badge bg-dark">{{ $refund->orderItem->order->uid ?? 'N/A' }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Product Name</th>
                                        <td>{{ $refund->orderItem->product_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Product Color</th>
                                        <td><span class="badge bg-secondary">{{ $refund->orderItem->product_color ?? 'N/A' }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Product Size</th>
                                        <td><span class="badge bg-info">{{ $refund->orderItem->product_size ?? 'N/A' }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Product Price</th>
                                        <td><strong>${{ number_format($refund->orderItem->price ?? 0, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Buyer Name</th>
                                        <td>{{ $refund->buyer_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Seller Name</th>
                                        <td>{{ $refund->seller->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Buying Date</th>
                                        <td>{{ optional($refund->orderItem->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                                    </tr>

                                    <tr>
                                        <th class="bg-light">Refund Request Date</th>
                                        <td>{{ optional($refund->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'processing' => 'info'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$refund->status] ?? 'secondary' }}">
                                                {{ ucfirst($refund->status ?? 'N/A') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            @if($refund->status === 'pending')
                                <a href="#" class="btn btn-success btn-sm">Approve</a>
                                <a href="#" class="btn btn-danger btn-sm">Reject</a>
                            @else
                                <span class="text-muted small">No actions available</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
