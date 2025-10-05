<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\SocialLink;

class SocialLinksController extends Controller{
    public function index(){
        $social_links = SocialLink::where('status', 'active')->get();
        $data = [
            'social_links' => $social_links
        ];
        return Helper::jsonResponse(true, 'About Page', 200, $data);
    }
}