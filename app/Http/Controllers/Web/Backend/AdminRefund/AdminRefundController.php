<?php

namespace App\Http\Controllers\Web\Backend\AdminRefund;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\RefundRequest;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class AdminRefundController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // $data = RefundRequest::orderBy('created_at', 'desc')->get();
            $data = RefundRequest::with('seller')
                ->orderBy('created_at', 'desc')
                ->get();


            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    if ($data->image) {
                        $url = asset($data->image);
                        return '<img src="' . $url . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    } else {
                        return '<img src="' . asset('default/logo.svg') . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    }
                })
                ->addColumn('status', function ($data) {
                    $color = match ($data->status) {
                        'approved' => 'green',
                        'rejected' => 'red',
                        'pending'  => 'orange',
                        default    => 'gray'
                    };

                    return '<span style="color: white; background-color: ' . $color . '; padding: 5px 10px; border-radius: 5px;">' . ucfirst($data->status) . '</span>';
                })

                ->addColumn('seller_name', function ($data) {
                    return $data->seller?->name ?? 'N/A';
                })

                ->addColumn('seller_image', function ($data) {
                    if ($data->seller && $data->seller->image) {
                        $url = asset($data->seller->image);
                        return '<img src="' . $url . '" alt="seller" width="50px" height="50px" style="margin-left:20px; border-radius:50%;">';
                    } else {
                        return '<img src="' . asset('default/user.png') . '" alt="seller" width="50px" height="50px" style="margin-left:20px; border-radius:50%;">';
                    }
                })


                ->addColumn('duration', function ($data) {
                    if (!$data->boosted_until) {
                        return '';
                    }
                    $boostedUntil = Carbon::parse($data->boosted_until);
                    $formattedDate = $boostedUntil->format('j F g:i A');
                    // Check if it's past
                    if ($boostedUntil->isPast()) {
                        return '<span class="badge bg-danger">' . $formattedDate . '</span> 
                <small class="text-danger">Boost expired</small>';
                    }

                    return '<span class="badge bg-success">' . $formattedDate . '</span>';
                })

                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                             <a href="#" type="button" onclick="goToOpen(' . $data->id . ')" class="btn btn-success fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-eye"></i>
                                </a>
                        </div>';
                })

                ->rawColumns(['status', 'action',  'duration', 'image','seller_name','seller_image'])
                ->make(true);
        }

        return view('backend.layouts.refundrequest.index');
    }

    // show all details about refund

   public function show($id)
{
    $refund = RefundRequest::with(['seller', 'orderItem.order'])->findOrFail($id);

    return view('backend.layouts.refundrequest.show', compact('refund'));
}

}
