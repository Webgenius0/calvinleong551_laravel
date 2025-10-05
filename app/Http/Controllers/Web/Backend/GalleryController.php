<?php

namespace App\Http\Controllers\Web\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class GalleryController extends Controller
{

    public function index()
    {
        return view("backend.layouts.gallery.index");
    }

    public function list()
    {
        $imagesDirectory = public_path("uploads/gallery/");
        $files = array_values(array_diff(scandir($imagesDirectory), ['.', '..']));
        $imageFiles = array_filter($files, function ($file) use ($imagesDirectory) {
            return is_file($imagesDirectory . $file);
        });

        $data = [];

        foreach ($imageFiles as $file) {
            $data[$file] = [
                'name' => encrypt($file),
                'src' => asset("uploads/gallery/$file")
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        // Validation (optional but recommended)
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        $files = $request->file('images');
        $imagesDirectory = public_path("uploads/gallery/");

        foreach ($files as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($imagesDirectory, $filename);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Images uploaded successfully'
        ]);
    }

    public function destroy($name)
    {

        $name = decrypt($name);
        $imagesDirectory = public_path("uploads/gallery/$name");

        if (file_exists($imagesDirectory)) {
            unlink($imagesDirectory);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted successfully'
        ]);
    }
}
