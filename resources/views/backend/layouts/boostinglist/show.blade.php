@extends('backend.app', ['title' => 'Show Boosting Payment'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Show</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Boosting Payment</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Show</li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card post-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">{{ Str::limit($boostPayment->title, 50) }}</h3>
                            <div class="card-options">
                                <a href="javascript:window.history.back()" class="btn btn-sm btn-primary">Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th>Thumbnail</th>
                                    <td>
                                        <a href="{{ asset($boostPayment->thumb ?? 'default/logo.png') }}" target="_blank"><img src="{{ asset($boostPayment->thumb ?? 'default/logo.png') }}" alt="" width="50" height="50" class="img-fluid"></a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Plan Name</th>
                                    <td>{{ $boostPayment->product->boostPlan->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $boostPayment->amount?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $boostPayment->status ?? 'N/A' }}</td>
                                </tr>
                                
                                
                               <tr>
    <th>Expire Date</th>
    <td>
        @if($boostPayment->boosted_until)
            @php
                $boostedUntil = \Carbon\Carbon::parse($boostPayment->boosted_until);
            @endphp

            {{ $boostedUntil->format('j M g:i a') }}

            @if($boostedUntil->isPast())
                <small class="badge bg-danger ms-2">Boosted Expired</small>
            @else
                <small class="badge bg-success ms-2">Active</small>
            @endif
        @else
            N/A
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
