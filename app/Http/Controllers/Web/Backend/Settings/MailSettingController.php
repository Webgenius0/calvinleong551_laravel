<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class MailSettingController extends Controller
{
    /**
     * Display mail settings page.
     *
     * @return View
     */
    public function index(): View
    {
        $settings = [
            'mail_mailer'       => env('MAIL_MAILER', ''),
            'mail_host'         => env('MAIL_HOST', ''),
            'mail_port'         => env('MAIL_PORT', ''),
            'mail_username'     => env('MAIL_USERNAME', ''),
            'mail_password'     => env('MAIL_PASSWORD', ''),
            'mail_encryption'   => env('MAIL_ENCRYPTION', ''),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
        ];

        return view('backend.layouts.settings.mail_settings', compact('settings'));
    }

    /**
     * Update mail settings.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'mail_mailer'       => 'nullable|string|regex:/^[\S]*$/',
            'mail_host'         => 'nullable|string|regex:/^[\S]*$/',
            'mail_port'         => 'nullable|string|regex:/^[\S]*$/',
            'mail_username'     => 'nullable|string|regex:/^[\S]*$/',
            'mail_password'     => 'nullable|string|regex:/^[\S]*$/',
            'mail_encryption'   => 'nullable|string|regex:/^[\S]*$/',
            'mail_from_address' => 'nullable|string|regex:/^[\S]*$/',
        ]);

        try {
            $envContent = File::get(base_path('.env'));
            $lineBreak  = "\n";
            $envContent = preg_replace([
                '/MAIL_MAILER=(.*)\s*/',
                '/MAIL_HOST=(.*)\s*/',
                '/MAIL_PORT=(.*)\s*/',
                '/MAIL_USERNAME=(.*)\s*/',
                '/MAIL_PASSWORD=(.*)\s*/',
                '/MAIL_ENCRYPTION=(.*)\s*/',
                '/MAIL_FROM_ADDRESS=(.*)\s*/',
            ], [
                'MAIL_MAILER=' . $request->mail_mailer . $lineBreak,
                'MAIL_HOST=' . $request->mail_host . $lineBreak,
                'MAIL_PORT=' . $request->mail_port . $lineBreak,
                'MAIL_USERNAME=' . $request->mail_username . $lineBreak,
                'MAIL_PASSWORD=' . '"' . $request->mail_password . '"' . $lineBreak,
                'MAIL_ENCRYPTION=' . $request->mail_encryption . $lineBreak,
                'MAIL_FROM_ADDRESS=' . '"' . $request->mail_from_address . '"' . $lineBreak,
            ], $envContent);

            File::put(base_path('.env'), $envContent);

            return back()->with('t-success', 'Updated successfully');
        } catch (Exception) {
            return back()->with('t-error', 'Failed to update');
        }
    }

    public function send(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'receiver'  => 'required|email|max:100',
            'subject'   => 'required|string|max:100',
            'content'   => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $data = $validator->validated();

            $receiver = $data['receiver'];
            $subject = $data['subject'];
            $content = $data['content'];

            Mail::to($receiver)->send(new TestMail($subject, $content));

            return back()->with('t-success', 'Mail sent successfully');
        } catch (Exception $e) {

            return back()->with('t-error', $e->getMessage());
        }
    }
}
