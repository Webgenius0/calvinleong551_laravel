<?php

namespace App\Repositories;

use App\Helpers\Helper;
use App\Models\Subcategory;
use App\Repositories\Interfaces\SubCategoryRepositoryInterface;

class SubCategoryRepository implements SubCategoryRepositoryInterface
{
    public function all()
    {
        return Subcategory::all();
    }

    public function find($id)
    {
        return Subcategory::find($id);
    }

    public function create(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = Helper::fileUpload($data['image'], 'subcategory', time() . '_' . getFileName($data['image']));
        }
        
        $data['slug'] = Helper::makeSlug(Subcategory::class, $data['name']);

        Subcategory::create($data);
    }

    public function update($id, array $data)
    {
        $category = Subcategory::findOrFail($id);

        if (isset($data['image'])) {
            if ($category->image && file_exists(public_path($category->image))) {
                Helper::fileDelete(public_path($category->image));
            }
            $data['image'] = Helper::fileUpload($data['image'], 'subcategory', time() . '_' . getFileName($data['image']));
        }

        $category->update($data);
    }

    public function delete($id)
    {
        $data = Subcategory::findOrFail($id);
        if ($data->image && file_exists(public_path($data->image))) {
            Helper::fileDelete(public_path($data->image));
        }
        $data->delete();
    }

    public function status($id)
    {
        $data = Subcategory::findOrFail($id);
        if (!$data) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Item not found.',
            ]);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
    }

}
