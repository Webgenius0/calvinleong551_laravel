<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Exception;

class LivewireController extends Controller
{
    public function index()
    {
        return view('backend.layouts.livewire.index');
    }

}
