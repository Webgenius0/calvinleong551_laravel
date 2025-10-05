<?php

namespace App\Http\Controllers\Web\Backend\CMS\Web\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\PageEnum;
use App\Enums\SectionEnum;
use App\Helpers\Helper;
use App\Models\CMS;
use Exception;
use App\Http\Requests\CmsRequest;
use App\Services\CmsService;
use Illuminate\Support\Str;

class HomeBannerController extends Controller
{
    protected $cmsService;

    public $page;
    public $component;
    public $section;

    public $sections;
    public $components;
    public $count;


    public function __construct(CmsService $cmsService)
    {
        $this->cmsService = $cmsService;

        $this->page = PageEnum::HOME;

        $this->component = ['title', 'sub_title', 'video'];
        $this->section = SectionEnum::BANNER;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = CMS::where('page', $this->page)->where('section', $this->section)->latest()->first();
        return view("backend.layouts.cms.index", ["data" => $data, "page" => $this->page->value, "section" => $this->section->value, "component" => $this->component, 'sections' => $this->sections]);
    }

    public function show($id)
    {
        $data = CMS::where('id', $id)->first();
        return view("backend.layouts.cms.show", ["data" => $data, "page" => $this->page->value, "section" => $this->section->value]);
    }

    public function content(CmsRequest $request)
    {
        $validatedData = $request->validated();
        try {
            $validatedData['page'] = $this->page;
            $validatedData['section'] = $this->section;
            $section = CMS::where('page', $this->page)->where('section', $this->section)->first();

            if ($request->hasFile('bg')) {
                if ($section && $section->bg && file_exists(public_path($section->bg))) {
                    Helper::fileDelete(public_path($section->bg));
                }
                $validatedData['bg'] = Helper::fileUpload($request->file('bg'), $this->section->value, time() . '_' . getFileName($request->file('bg')));
            }

            if ($request->hasFile('image')) {
                if ($section && $section->image && file_exists(public_path($section->image))) {
                    Helper::fileDelete(public_path($section->image));
                }
                $validatedData['image'] = Helper::fileUpload($request->file('image'), $this->section->value, time() . '_' . getFileName($request->file('image')));
            }

            if ($request->hasFile('video')) {
                // Check if old video exists and delete it
                if ($section && $section->video && file_exists(public_path($section->video))) {
                    unlink(public_path($section->video)); // delete old video
                }

                // Handle the new video upload
                $file = $request->file('video');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/videos'), $filename);

                // Update the video path in your data array for saving to DB
                $validatedData['video'] = 'uploads/videos/' . $filename;
            }



            if ($request->has('rating')) {
                $validatedData['metadata']['rating'] = $validatedData['rating'];
                unset($validatedData['rating']);
            }

            if ($section) {
                CMS::where('page', $validatedData['page'])->where('section', $validatedData['section'])->update($validatedData);
            } else {

                // Generate a unique slug
                do {
                    $validatedData['slug'] = 'slug_' . Str::random(8);
                } while (CMS::where('slug', $validatedData['slug'])->exists());

                CMS::create($validatedData);
            }

            return redirect()->route("admin.cms.{$this->page->value}.{$this->section->value}.index")->with('t-success', 'Updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', $e->getMessage());
        }
    }

    public function display()
    {
        try {
            $pages = CMS::where('page', $this->page)->get();
            foreach ($pages as $page) {
                $page->update(['is_display' => !$page->is_display]);
            }
            return back()->with('t-success', 'Display status updated successfully.');
        } catch (Exception $e) {

            return back()->with('t-error', $e->getMessage());
        }
    }
}
