<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PrivecyAndTerms;
use Illuminate\Http\Request;

class PrivecyPolicyController extends Controller
{
    public function index()
    {
       $data = PrivecyAndTerms::get();
       return response()->json([
           'status' => true,
           'message' => 'Privacy and Terms fetched successfully',
           'data' => $data
       ]);
    }
}
