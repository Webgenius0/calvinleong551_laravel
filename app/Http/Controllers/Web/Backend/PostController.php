<?php

namespace App\Http\Controllers\Web\Backend;

use App\Helpers\Helper;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\Subcategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;


class PostController extends Controller
{

    public $route = 'admin.post';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Post::with(['category', 'subcategory', 'user'])->orderBy('id', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('category', function ($data) {
                    return "<a href='" . route('admin.category.show', $data->category_id) . "'>" . $data->category->name . "</a>";
                })
                ->addColumn('subcategory', function ($data) {
                    return "<a href='" . route('admin.subcategory.show', $data->subcategory_id) . "'>" . $data->subcategory->name . "</a>";
                })
                ->addColumn('author', function ($data) {
                    return "<a href='" . route('admin.users.show', $data->user_id) . "'>" . $data->user->name . "</a>";
                })
                ->addColumn('title', function ($data) {
                    return Str::limit($data->title, 20);
                })
                ->addColumn('thumbnail', function ($data) {
                    $url = asset($data->thumbnail && file_exists(public_path($data->thumbnail)) ? $data->thumbnail : 'default/logo.svg');
                    return '<img src="' . $url . '" alt="image" style="width: 50px; max-height: 100px; margin-left: 20px;">';
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

                                <a href="#" type="button" onclick="goToOpen(' . $data->id . ')" class="btn btn-success fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-eye"></i>
                                </a>

                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['category', 'subcategory', 'author', 'title', 'thumbnail', 'status', 'action'])
                ->make();
        }

        return view("backend.layouts.post.index", ['route' => $this->route]);
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::where('status', 'active')->get();
        return view('backend.layouts.post.create', [
            'categories' => $categories,
            'route' => $this->route
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();

            $post = new Post();

            $post->user_id = auth('web')->user()->id;

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
                session()->put('t-error', 'Please select at least one image and maximum 3 images');
            }

            session()->put('t-success', 'post created successfully');
        } catch (Exception $e) {

            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.post.index')->with('t-success', 'post created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post, $id)
    {
        $post = Post::with(['category', 'subcategory', 'user'])->where('id', $id)->first();
        return view('backend.layouts.post.show', [
            'post' => $post,
            'route' => $this->route
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post, $id)
    {
        $post = Post::findOrFail($id);
        $categories = Category::where('status', 'active')->get();
        $subcategories = Subcategory::where('status', 'active')->get();
        return view('backend.layouts.post.edit', [
            'post' => $post,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'route' => $this->route
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
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
            return redirect()->back()->withErrors($validator)->withInput();
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

            //image insert
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

            session()->put('t-success', 'post updated successfully');
        } catch (Exception $e) {

            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.post.edit', $post->id)->with('t-success', 'post updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
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
            return response()->json([
                'status' => 't-success',
                'message' => 'Your action was successful!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Your action was successful!'
            ]);
        }
    }

    public function status(int $id): JsonResponse
    {
        $data = Post::findOrFail($id);
        if (!$data) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Item not found.',
            ]);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!',
        ]);
    }
}
