<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Image;
use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function posts(): JsonResponse
    {
        $posts = Post::with(['category', 'subcategory', 'user', 'images'])->where('status', 'active')->get();
        $data = [
            'posts' => $posts
        ];
        return Helper::jsonResponse(true, 'get all posts', 200, $data);
    }

    public function post($slug): JsonResponse
    {
        $post = Post::with(['category', 'subcategory', 'user', 'images'])->where('slug', $slug)->where('status', 'active')->first();
        $data = [
            'post' => $post
        ];
        return Helper::jsonResponse(true, 'get single post', 200, $data);
    }

    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $posts = Post::with(['category', 'subcategory', 'user', 'images'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();
        $data = [
            'posts' => $posts
        ];
        return Helper::jsonResponse(true, 'get all posts', 200, $data);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|max:250',
            'content'           => 'required|string',
            'thumbnail'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'category_id'       => 'required|exists:categories,id',
            'subcategory_id'    => 'required|exists:subcategories,id',
            'images'            => 'nullable|array|max:3',
            'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        try {
            $data = $validator->validated();

            $post = new Post();

            $post->user_id = auth('api')->user()->id;

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'post', time() . '_' . getFileName($request->file('thumbnail')));
            }

            $post->slug = Helper::makeSlug(Post::class, $data['title']);

            $post->title = $data['title'];
            $post->thumbnail = $data['thumbnail'];
            $post->content = $data['content'];
            $post->category_id = $data['category_id'];
            $post->subcategory_id = $data['subcategory_id'];
            $post->save();

            if (isset($request['images']) && count($request['images']) > 0 && count($request['images']) <= 3) {
                foreach ($request['images'] as $image) {
                    $imageName = 'images_' . Str::random(10);
                    $image = Helper::fileUpload($image, 'post', $imageName);
                    Image::create(['post_id' => $post->id, 'path' => $image]);
                }
            } else {
                return Helper::jsonResponse(false, 'At least one image is required and maximum 3 images allowed', 400);
            }

            return Helper::jsonResponse(true, 'Post updated successfully', 200, $post);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function show($id): JsonResponse
    {
        $user = auth('api')->user();
        $post = Post::with(['category', 'subcategory', 'user', 'images'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();
        $data = [
            'post' => $post
        ];
        return Helper::jsonResponse(true, 'get single posts', 200, $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|max:250',
            'content'           => 'required|string',
            'thumbnail'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'category_id'       => 'required|exists:categories,id',
            'subcategory_id'    => 'required|exists:subcategories,id',
            'images'            => 'nullable|array|max:3',
            'images.*'          => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        try {
            $data = $validator->validated();

            $post = Post::findOrFail($id);

            if ($request->hasFile('thumbnail')) {
                $validate['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'post', time() . '_' . getFileName($request->file('thumbnail')));
            }

            $post->title = $data['title'];
            $post->thumbnail = $data['thumbnail'] ?? $post->thumbnail;
            $post->content = $data['content'];
            $post->category_id = $data['category_id'];
            $post->subcategory_id = $data['subcategory_id'];
            $post->save();

            $image_count = Image::where('post_id', $post->id)->count();
            $new_images_count = $request->has('images') ? count($request['images']) : 0;

            if (($image_count + $new_images_count) > 3) {
                session()->put('t-error', 'Please select at most 3 images');
            } else {
                if ($new_images_count > 0) {
                    foreach ($request->file('images') as $image) {
                        $imageName = 'images_' . Str::random(10);
                        $uploadedImagePath = Helper::fileUpload($image, 'post', $imageName);
                        Image::create(['post_id' => $post->id, 'path' => $uploadedImagePath]);
                    }
                }
            }

            return Helper::jsonResponse(true, 'Post updated successfully', 200, $post);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {

            $data = Post::findOrFail($id);

            if ($data->thumbnail && file_exists(public_path($data->thumbnail))) {
                Helper::fileDelete(public_path($data->thumbnail));
            }

            $images = Image::where('post_id', $data->id)->get();
            if (count($images) > 0) {
                foreach ($images as $image) {
                    if ($image->path && file_exists(public_path($image->path))) {
                        Helper::fileDelete(public_path($image->path));
                    }
                    $image->delete();
                }
            }

            $data->delete();
            return Helper::jsonResponse(true, 'Post deleted successfully', 200);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function status(int $id): JsonResponse
    {
        $data = Post::findOrFail($id);
        if (!$data) {
            return Helper::jsonResponse(false, 'Item not found.', 404);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
        return Helper::jsonResponse(true, 'Post status updated successfully', 200);
    }
}
