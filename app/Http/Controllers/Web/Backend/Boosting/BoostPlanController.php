<?php

namespace App\Http\Controllers\Web\Backend\Boosting;

use Exception;
use App\Models\BoostPlan;
use Illuminate\Http\Request;
use App\Models\ProductUploadsTips;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class BoostPlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BoostPlan::all();
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
                ->addColumn('is_default', function ($data) {
                    return $data->is_default ? '<span class="badge bg-success">Regular Boost</span>' : '<span class="badge bg-secondary">Custom Boost</span>';
                })

                ->addColumn('duration', function ($data) {
                    return $data->duration . ' days';
                })

                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">

                                <a href="#" type="button" onclick="goToEdit(' . $data->id . ')" class="btn btn-primary fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-edit"></i>
                                </a>

                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['status', 'action', 'is_default', 'duration'])
                ->make();
        }
        return view('backend.layouts.boosting.index');
    }


    public function create()
    {
        return view('backend.layouts.boosting.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'duration' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            BoostPlan::where('is_default', true)->update(['is_default' => false]);
        }

        $data = new BoostPlan();
        $data->name = $request->name;
        $data->duration = $request->duration;
        $data->price = $request->price;
        $data->is_default = $request->boolean('is_default');
        $data->save();

        return redirect()->route('admin.boost-plan.index')->with('success', 'Boost Plan created successfully.');
    }
    public function edit($id)
    {
        $data = BoostPlan::findOrFail($id);
        return view('backend.layouts.boosting.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
      
        $data = BoostPlan::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'duration' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            // Reset all other rows except this one
            BoostPlan::where('is_default', true)->where('id', '!=', $data->id)->update(['is_default' => false]);
        }

        $data->name = $request->name;
        $data->duration = $request->duration;
        $data->price = $request->price;
          $data->is_default = $request->boolean('is_default') ? 1 : 0;
        $data->save();

        return redirect()->route('admin.boost-plan.index')->with('success', 'Boost Plan updated successfully.');
    }

     public function destroy(string $id)
    {
        try {
            BoostPlan::where('id', $id)->delete();
            return response()->json([
                'status' => 't-success',
                'message' => 'Your action was successful!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => $e->getMessage(),
            ]);
        }
    }

}
