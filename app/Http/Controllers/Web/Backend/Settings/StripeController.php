<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class StripeController extends Controller
{
    /**
     * Display mail settings page.
     *
     * @return View
     */
    public function index(): View
    {
        $settings = [
            'stripe_key'            => env('STRIPE_KEY', ''),
            'stripe_secret'         => env('STRIPE_SECRET', ''),
            'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', '')
        ];

        return view('backend.layouts.settings.stripe_settings', compact('settings'));
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
            'stripe_key'            => 'nullable|string|regex:/^[\S]*$/',
            'stripe_secret'         => 'nullable|string|regex:/^[\S]*$/',
            'stripe_webhook_secret' => 'nullable|string|regex:/^[\S]*$/',
            'stripe_checkout_webhook_secret' => 'nullable|string|regex:/^[\S]*$/',
            'stripe_rented_webhook_secret' => 'nullable|string|regex:/^[\S]*$/',
        ]);

        try {
            $envContent = File::get(base_path('.env'));
            $lineBreak  = "\n";
            $envContent = preg_replace([
                '/STRIPE_KEY=(.*)\s*/',
                '/STRIPE_SECRET=(.*)\s*/',
                '/STRIPE_WEBHOOK_SECRET=(.*)\s*/',
                '/STRIPE_CHECKOUT_WEBHOOK_SECRET=(.*)\s*/',
                '/STRIPE_RENTED_WEBHOOK_SECRET=(.*)\s*/',
            ], [
                'STRIPE_KEY=' . $request->stripe_key . $lineBreak,
                'STRIPE_SECRET=' . $request->stripe_secret . $lineBreak,
                'STRIPE_WEBHOOK_SECRET=' . $request->stripe_webhook_secret . $lineBreak,
                'STRIPE_CHECKOUT_WEBHOOK_SECRET=' . $request->stripe_checkout_webhook_secret . $lineBreak,
                'STRIPE_rented_WEBHOOK_SECRET=' . $request->stripe_rented_webhook_secret . $lineBreak,
            ], $envContent);

            File::put(base_path('.env'), $envContent);

            return back()->with('t-success', 'Updated successfully');
        } catch (Exception) {
            return back()->with('t-error', 'Failed to update');
        }
    }



    public function updateOnboarding(Request $request): RedirectResponse
    {
        $request->validate([
            'stripe_client_id'    => 'nullable|string|regex:/^\S+$/',
            'stripe_redirect_url' => 'nullable|string|regex:/^\S+$/',
        ]);

        try {
            $envPath = base_path('.env');
            $envContent = File::exists($envPath) ? File::get($envPath) : '';

            $keys = [
                'STRIPE_CLIENT_ID'    => $request->stripe_client_id,
                'STRIPE_REDIRECT_URI' => $request->stripe_redirect_url,
            ];

            foreach ($keys as $key => $value) {
                $value = trim($value); // remove extra spaces
                if (preg_match("/^{$key}=.*$/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}";
                }
            }

            File::put($envPath, $envContent);

            // Clear caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');

            return back()->with('t-success', 'Updated Onboarding successfully');
        } catch (\Exception $e) {
            return back()->with('t-error', 'Failed to update: ' . $e->getMessage());
        }
    }



}
