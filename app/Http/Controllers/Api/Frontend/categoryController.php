<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Category;

class categoryController extends Controller
{
    public function index()
    {
        $category = Category::where('status', 'active')->get();
        $data = [
            'categories' => $category
        ];
        return Helper::jsonResponse(true, 'Category', 200, $data);

    }
}
