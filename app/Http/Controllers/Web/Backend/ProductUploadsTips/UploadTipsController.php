<?php

namespace App\Http\Controllers\Web\Backend\ProductUploadsTips;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\ProductUploadsTips;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class UploadTipsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductUploadsTips::all();
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
                ->rawColumns(['image', 'status', 'action'])
                ->make();
        }
        return view('backend.layouts.product_uploads_tips.index');
    }


    public function create()
    {
        return view('backend.layouts.product_uploads_tips.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate and store the upload tips data
        $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB
        ]);

        $validate = validator($request->all(), [
            'title' => 'required|string|max:255',
            'sub_title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $data = new ProductUploadsTips();
        $data->title = $request->title;
        $data->sub_title = $request->sub_title;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'product_uploads_tips';
            $file->move(public_path($path), $filename);

            $data->image = $path . '/' . $filename;
        }


        $data->save();

        return redirect()->route('admin.upload-tips.index')->with('success', 'Upload Tips created successfully.');
    }

    public function edit($id)
    {

        $data = ProductUploadsTips::findOrFail($id);
        return view('backend.layouts.product_uploads_tips.edit', compact('data'));
    }

    // update method to handle the update of upload tips
    public function update(Request $request, $id)
    {
        $data = ProductUploadsTips::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // 5MB
        ]);


        $data->title = $request->title;
        $data->sub_title = $request->sub_title;


        if ($request->hasFile('image')) {
            // Unlink old image
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            // Upload new image
            $file = $request->file('image');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'product_uploads_tips';
            $file->move(public_path($path), $filename);

            $data->image = $path . '/' . $filename;
        }

        $data->save();

        return redirect()->route('admin.upload-tips.index')->with('success', 'Upload Tips updated successfully.');
    }
}
