<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Image;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function index($post_id) {
        $images = Image::where('post_id', $post_id)->get();
        $data = [
            'images' => $images
        ];
        return Helper::jsonResponse(true, 'Posts fetched successfully', 200, $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id'        => 'required|exists:posts,id',
            'images'         => 'required|array|max:3',
            'images.*'       => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        try {
            $data = $validator->validated();
            $image_count = Image::where('post_id', $data['post_id'])->count();
            $total_image_count = $image_count + count($request['images']);

            if ($total_image_count > 3 ) {
                return Helper::jsonResponse(false, 'Maximum 3 images allowed', 422, $validator->errors());
            }

            //image upload code start
            if (!empty($request['images']) && count($request['images']) > 0 && count($request['images']) <= 3) {
                foreach ($request['images'] as $image) {
                    $imageName = 'images_' . Str::random(10);
                    $image = Helper::fileUpload($image, 'posts', $imageName);
                    Image::create(['post_id' => $data['post_id'], 'image' => $image]);
                }
            }else{
                return Helper::jsonResponse(false, 'Maximum 3 images allowed', 422, $validator->errors());
            }
            //image upload code end

            // Retrieve the post along with associated category and images
            $image = Image::where('post_id', $data['post_id'])->get();

            return Helper::jsonResponse(true, 'Post Image created successfully', 200, $image);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'An error occurred while creating the new post', 500, ['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $image = Image::find($id);
        if (!$image) {
            return Helper::jsonResponse(false, 'Image not found', 404);
        }
        Helper::fileDelete(public_path($image->path));
        $image->delete();
        return Helper::jsonResponse(true, 'Image deleted successfully', 200, $image);
    }

}
