@extends('backend.app', ['title' => 'Show Transaction'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Transaction</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Transaction</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Show</li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card transaction-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">{{ Str::limit($transaction->title, 50) }}</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th>Title</th>
                                    <td>{{ $transaction->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>ID</th>
                                    <td>{{ $transaction->trx_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td><a href="{{ route('admin.users.show', $transaction->user->id) }}">{{ $transaction->user->name ?? 'N/A' }}</a></td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $transaction->amount ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Gateway</th>
                                    <td>{{ $transaction->gateway ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>{{ $transaction->type ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $transaction->status ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $transaction->created_at ? $transaction->created_at : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Metadata</th>
                                    <td>
                                        @if($transaction->metadata != null)
                                        <pre>{{ json_encode(json_decode($transaction->metadata), JSON_PRETTY_PRINT) }}</pre>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>
                                        @if($transaction->metadata_json != null)
                                        @foreach($transaction->metadata_json as $key => $value)
                                            @if($key == 'product' && $value != null)
                                            <a href="{{ route('admin.product.show', $value) }}" class="btn btn-primary">View Product</a>
                                            @endif
                                            @if($key == 'owner' && $value != null)
                                            <a href="{{ route('admin.users.show', $value) }}" class="btn btn-primary">View Owner</a>
                                            @endif
                                            @if($key == 'customer' && $value != null)
                                            <a href="{{ route('admin.users.show', $value) }}" class="btn btn-primary">View Customer</a>
                                            @endif
                                            @if($key == 'booking' && $value != null)
                                            <a href="{{ route('admin.booking.show', $value) }}" class="btn btn-primary">View Booking</a>
                                            @endif
                                        @endforeach
                                        @endif
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

@endpush