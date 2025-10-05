<?php

namespace App\Http\Controllers\Web\Backend\Boosting;

use Exception;
use Carbon\Carbon;
use App\Models\BoostPlan;
use Illuminate\Http\Request;
use App\Models\BoostingPayment;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class BoostingListController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BoostingPayment::with('product')->with('product.boostPlan')->orderBy('created_at', 'desc')->get();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';

                    $status = '<div class="d-flex justify-content-center align-items-center">';
                    $status .= '<div class="form-check form-switch" style="position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX(' . $sliderTranslateX . ');"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('plan_name', function ($data) {
                    return $data->product->boostPlan->name ?? '';
                })
                ->addColumn('is_default', function ($data) {
                    return $data->is_default
                        ? '<span class="badge bg-success">Regular Boost</span>'
                        : '<span class="badge bg-secondary">Custom Boost</span>';
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

                ->rawColumns(['status', 'action', 'is_default', 'duration', 'plan_name'])
                ->make(true);
        }

        return view('backend.layouts.boostinglist.index');
    }

    public function show(Request $request, $id)
    {
        $boostPayment = BoostingPayment::with('product')->with('product.boostPlan')->findOrFail($id);
        // $boostPayment = BoostingPayment::findOrFail($id);
        return view('backend.layouts.boostinglist.show', compact('boostPayment'));
    }
}
