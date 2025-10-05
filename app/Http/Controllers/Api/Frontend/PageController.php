<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pages->count() > 0) {
            $data = [
                'pages' => $pages,
            ];

            return Helper::jsonResponse(true, 'Home Page', 200, $data);
        }

        return Helper::jsonResponse(false, 'No pages found', 404);
    }

    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if ($page) {
            return Helper::jsonResponse(true, 'Page found', 200, ['page' => $page]);
        }

        return Helper::jsonResponse(false, 'Page not found', 404);
    }

}
