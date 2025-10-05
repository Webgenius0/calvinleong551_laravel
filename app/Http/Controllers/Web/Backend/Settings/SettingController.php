<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display the system settings page.
     *
     * @return View
     */
    public function index(): View
    {
        $setting = Setting::latest('id')->first();
        return view('backend.layouts.settings.general_settings', compact('setting'));
    }

    /**
     * Update the system settings.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name'           => 'nullable|string|max:50',
            'title'          => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:500',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|string|email|max:100',
            'copyright'      => 'nullable|string|max:255',
            'keywords'       => 'nullable|string|max:255',
            'author'         => 'nullable|string|max:100',
            'address'        => 'nullable|string|max:255',
            'favicon'        => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
            'thumbnail'      => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        try {
            $setting = Setting::first();
            
            if ($request->hasFile('favicon')) {
                if ($setting && $setting->favicon && file_exists(public_path($setting->favicon))) {
                    Helper::fileDelete(public_path($setting->favicon));
                }
                $validatedData['favicon'] = Helper::fileUpload($request->file('favicon'), 'settings', time() . '_' . getFileName($request->file('favicon')));
            }

            if ($request->hasFile('thumbnail')) {
                if ($setting && $setting->thumbnail && file_exists(public_path($setting->thumbnail))) {
                    Helper::fileDelete(public_path($setting->thumbnail));
                }
                $validatedData['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'settings', time() . '_' . getFileName($request->file('thumbnail')));
            }

            Setting::updateOrCreate(['id' => 1], $validatedData);
            return back()->with('t-success', 'Updated successfully');
            
        } catch (Exception $e) {
            return back()->with('t-error', 'Failed to update' . $e->getMessage());
        }
    }
}
