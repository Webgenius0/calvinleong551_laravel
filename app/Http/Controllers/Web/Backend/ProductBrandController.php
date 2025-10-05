<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class ProductBrandController extends Controller
{
    public function index(Request $request)
    {
        // $data = Subcategory::with(['subcategoryBrand','subcategoryColor','subcategoryCondition','subcategoryMaterial','subcategorySize'])->get();
        // dd($data);
        if ($request->ajax()) {
            $data = Subcategory::with(['subcategoryBrand', 'subcategoryColor', 'subcategoryCondition', 'subcategoryMaterial', 'subcategorySize'])->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('subcategoryBrand', function ($data) {
                    return $data->subcategoryBrand->map(function ($brand) {
                        return '<span class="badge bg-primary">' . $brand->brand_name . '</span>';
                    })->implode(' ');
                })

                ->addColumn('subcategoryColor', function ($data) {
                    return $data->subcategoryColor->map(function ($color) {
                        return '<span class="badge bg-dark">' . $color->color_name . '</span>';
                    })->implode(' ');
                })
                ->addColumn('subcategoryColorCode', function ($data) {
                    return $data->subcategoryColor->map(function ($color) {
                        return '<span class="badge bg-success">' . $color->color_code . '</span>';
                    })->implode(' ');
                })


                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
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
                ->rawColumns(['subcategoryColor', 'subcategoryBrand', 'subcategoryColorCode', 'status', 'action'])
                ->make();
        }
        return view("backend.layouts.brand.index");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subcategory = Subcategory::where('status', 'active')->get();
        return view('backend.layouts.brand.create', compact('subcategory'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subcategory_id'    => 'required|exists:subcategories,id',
            'brands'            => 'array',
            'sizes'             => 'array',
            'materials'         => 'array',
            'condition_title'   => 'array',
            'condition_subtitle' => 'array',
            'color_name'        => 'array',
            'color_code'        => 'array',
        ]);

        // ✅ Get the subcategory_id from the form
        $sub_category_id = $request->subcategory_id;

        // 1. Insert Brands
        if ($request->brands) {
            foreach ($request->brands as $brand) {
                if (!empty($brand)) {
                    DB::table('sub_category_brands')->insert([
                        'sub_category_id' => $sub_category_id,
                        'brand_name'      => $brand,
                        'status'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        // 2. Insert Sizes
        if ($request->sizes) {
            foreach ($request->sizes as $size) {
                if (!empty($size)) {
                    DB::table('sub_category_sizes')->insert([
                        'sub_category_id' => $sub_category_id,
                        'size'            => $size,
                        'status'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        // 3. Insert Materials
        if ($request->materials) {
            foreach ($request->materials as $material) {
                if (!empty($material)) {
                    DB::table('sub_category_materials')->insert([
                        'sub_category_id' => $sub_category_id,
                        'material_name'   => $material,
                        'status'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        // 4. Insert Conditions
        if ($request->condition_title && $request->condition_subtitle) {
            foreach ($request->condition_title as $key => $title) {
                $subtitle = $request->condition_subtitle[$key] ?? null;
                if (!empty($title) || !empty($subtitle)) {
                    DB::table('sub_category_conditions')->insert([
                        'sub_category_id' => $sub_category_id,
                        'title'           => $title,
                        'condition'       => $subtitle,
                        'status'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        // 5. Insert Colors
        if ($request->color_name && $request->color_code) {
            foreach ($request->color_name as $key => $cname) {
                $ccode = $request->color_code[$key] ?? null;
                if (!empty($cname) || !empty($ccode)) {
                    DB::table('sub_category_colors')->insert([
                        'sub_category_id' => $sub_category_id,
                        'color_name'      => $cname,
                        'color_code'      => $ccode,
                        'status'          => 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        return redirect()->route('admin.brand.index')
            ->with('success', 'Subcategory attributes saved successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Subcategory $subcategory, $id)
    {
        $subcategory = $this->subCategoryRepository->find($id);
        return view('backend.layouts.subcategory.edit', compact('subcategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $subcategory = Subcategory::find($id);          // Current subcategory
        $allSubcategories = Subcategory::all();         // All subcategories for dropdown

        return view('backend.layouts.brand.edit', compact('subcategory', 'allSubcategories'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 1️⃣ Find the subcategory
        $subcategory = Subcategory::findOrFail($id);

        $subcategory->subcategoryBrand()->delete(); // Remove old ones
        if ($request->has('brands')) {
            foreach ($request->brands as $brandName) {
                if ($brandName) {
                    $subcategory->subcategoryBrand()->create([
                        'brand_name' => $brandName
                    ]);
                }
            }
        }

        // 4️⃣ Update Sizes
        $subcategory->subcategorySize()->delete();
        if ($request->has('sizes')) {
            foreach ($request->sizes as $size) {
                if ($size) {
                    $subcategory->subcategorySize()->create([
                        'size' => $size
                    ]);
                }
            }
        }

        // 5️⃣ Update Materials
        $subcategory->subcategoryMaterial()->delete();
        if ($request->has('materials')) {
            foreach ($request->materials as $material) {
                if ($material) {
                    $subcategory->subcategoryMaterial()->create([
                        'material_name' => $material
                    ]);
                }
            }
        }

        // 6️⃣ Update Conditions
        $subcategory->subcategoryCondition()->delete();
        if ($request->has('condition_title')) {
            foreach ($request->condition_title as $index => $title) {
                $subtitle = $request->condition_subtitle[$index] ?? null;
                if ($title || $subtitle) {
                    $subcategory->subcategoryCondition()->create([
                        'title' => $title,
                        'condition' => $subtitle
                    ]);
                }
            }
        }

        // 7️⃣ Update Colors
        $subcategory->subcategoryColor()->delete();
        if ($request->has('color_name')) {
            foreach ($request->color_name as $index => $colorName) {
                $colorCode = $request->color_code[$index] ?? '#000000';
                if ($colorName) {
                    $subcategory->subcategoryColor()->create([
                        'color_name' => $colorName,
                        'color_code' => $colorCode
                    ]);
                }
            }
        }

        return redirect()->route('admin.brand.index')
            ->with('success', 'Brand updated successfully!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $data = Subcategory::find($id);
            $data->delete();
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

    public function status(int $id)
    {
        try {
            $data = Subcategory::find($id);
            $data->status = $data->status === 'active' ? 'inactive' : 'active';
            $data->save();
            return response()->json([
                'status' => 't-success',
                'message' => 'Your action was successful!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
