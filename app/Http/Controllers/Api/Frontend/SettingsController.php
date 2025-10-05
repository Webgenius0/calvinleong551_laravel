<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingsController extends Controller{
    public function index(){
        $settings = Setting::first();
        $data = [
            'settings' => $settings
        ];
        return Helper::jsonResponse(true, 'About Page', 200, $data);
    }
}