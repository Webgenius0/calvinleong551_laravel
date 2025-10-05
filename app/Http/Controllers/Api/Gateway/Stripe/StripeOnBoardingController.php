<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Exception;
use Stripe\Payout;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Balance;
use App\Models\User;
use Google\Rpc\Help;

use App\Helpers\Helper;
use Stripe\AccountLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Validator;

class StripeOnBoardingController extends Controller
{

    // public function redirectToStripeConnect()
    // {
    //     $userId = auth('api')->id();

    //     if (!$userId) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }

    //     $userOnboarding = User::where('id', $userId)->first();
    //     $userOnboarding->stripe_account_id;

    //     if (!$userOnboarding || $userOnboarding->stripe_account_id !== null) {
    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'User Already Onboarded!'
    //         ], 200);
    //     }


    //     $clientId = env('STRIPE_CLIENT_ID');

    //     // Redirect URL for Stripe OAuth
    //     // $redirectUri = app()->environment('local')
    //     //     ? env('STRIPE_REDIRECT_URI_LOCAL')
    //     //     : env('STRIPE_REDIRECT_URI');

    //     //     Log::info('Stripe redirect URI:', ['redirect_uri' => $redirectUri]);

    //     $redirectUri = "https://kimsingh92.softvencefsd.xyz/api/stripe/connect/callback";

    //     $stripeUrl = "https://connect.stripe.com/oauth/authorize" .
    //         "?response_type=code" .
    //         "&client_id={$clientId}" .
    //         "&scope=read_write" .
    //         "&redirect_uri={$redirectUri}" .
    //         "&state={$userId}";

    //     return response()->json([
    //         'status' => true,
    //         'url' => $stripeUrl
    //     ]);
    // }

    public function redirectToStripeConnect()
{
    $userId = auth('api')->id();

    if (!$userId) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $user = User::find($userId);

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'User not found'], 404);
    }

    // If stripe_account_id exists, check account status
    if ($user->stripe_account_id) {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $account = Account::retrieve($user->stripe_account_id);

            $isActive = ($account->charges_enabled && $account->payouts_enabled && $account->details_submitted);

            if ($isActive) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User already onboarded with an active Stripe account!'
                ], 400);
            } else {
                // Not active → send onboarding link again
                $accountLink = AccountLink::create([
                    'account'     => $user->stripe_account_id,
                    'refresh_url' => route('api.payment.stripe.account.connect.refresh', ['account_id' => $user->stripe_account_id]),
                    'return_url'  => route('api.payment.stripe.account.connect.success', ['account_id' => $user->stripe_account_id]),
                    'type'        => 'account_onboarding',
                ]);

                return response()->json([
                    'status' => true,
                    'url'    => $accountLink->url
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching Stripe account: ' . $e->getMessage()
            ], 500);
        }
    }

    // No Stripe account yet → start OAuth
    $clientId    = env('STRIPE_CLIENT_ID');
    $redirectUri = "https://kimsingh92.softvencefsd.xyz/api/stripe/connect/callback";

    $stripeUrl = "https://connect.stripe.com/oauth/authorize" .
        "?response_type=code" .
        "&client_id={$clientId}" .
        "&scope=read_write" .
        "&redirect_uri={$redirectUri}" .
        "&state={$userId}";

    return response()->json([
        'status' => true,
        'url'    => $stripeUrl
    ]);
}


    /**
     * Step 2: Handle OAuth callback
     */
    // public function handleStripeConnectCallback(Request $request)
    // {
    //     $code   = $request->code;
    //     $userId = $request->state;

    //     if (!$code) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Stripe authorization code missing.'
    //         ], 400);
    //     }

    //     // Exchange code for access token + connected account ID
    //     $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
    //         'client_secret' => env('STRIPE_SECRET'),
    //         'code'          => $code,
    //         'grant_type'    => 'authorization_code',
    //     ]);

    //     if ($response->failed()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to connect Stripe account.'
    //         ], 500);
    //     }

    //     $connectedAccountId = $response->json()['stripe_user_id'];

    //     // Save account ID in DB
    //     $user = User::find($userId);
    //     if (!$user) {
    //         return response()->json(['status' => false, 'message' => 'User not found'], 404);
    //     }

    //     $user->stripe_account_id = $connectedAccountId;
    //     $user->save();

    //     // Retrieve Stripe account
    //     Stripe::setApiKey(env('STRIPE_SECRET'));
    //     $account = Account::retrieve($connectedAccountId);

    //     if (!$account->payouts_enabled) {
    //         // User still needs to provide info -> send them to onboarding link
    //         $accountLink = AccountLink::create([
    //             'account'     => $connectedAccountId,
    //             'refresh_url' => route('api.payment.stripe.account.connect.refresh', ['account_id' => $connectedAccountId]),
    //             'return_url'  => route('api.payment.stripe.account.connect.success', ['account_id' => $connectedAccountId]),
    //             'type'        => 'account_onboarding',
    //         ]);

    //         return redirect($accountLink->url); // 🔥 redirect directly
    //     }

    //     // If payouts enabled, account is ready!
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Stripe account connected and payouts enabled!',
    //         'stripe_account_id' => $connectedAccountId,
    //     ]);
    // }
public function handleStripeConnectCallback(Request $request)
{
    $code   = $request->code;
    $userId = $request->state;

    if (!$code) {
        return response()->json([
            'status' => false,
            'message' => 'Stripe authorization code missing.'
        ], 400);
    }

    // Exchange code for access token + connected account ID
    $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
        'client_secret' => env('STRIPE_SECRET'),
        'code'          => $code,
        'grant_type'    => 'authorization_code',
    ]);

    if ($response->failed()) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to connect Stripe account.'
        ], 500);
    }

    $connectedAccountId = $response->json()['stripe_user_id'];

    // Save account ID in DB
    $user = User::find($userId);
    if (!$user) {
        return response()->json(['status' => false, 'message' => 'User not found'], 404);
    }

    $user->stripe_account_id = $connectedAccountId;
    $user->save();

    // Retrieve Stripe account
    Stripe::setApiKey(env('STRIPE_SECRET'));
    $account = Account::retrieve($connectedAccountId);

    // Check if account is fully active
    $isActive = ($account->charges_enabled && $account->payouts_enabled && $account->details_submitted);

    if (!$isActive) {
        // User still needs to complete onboarding → redirect
        $accountLink = AccountLink::create([
            'account'     => $connectedAccountId,
            'refresh_url' => route('api.payment.stripe.account.connect.refresh', ['account_id' => $connectedAccountId]),
            'return_url'  => route('api.payment.stripe.account.connect.success', ['account_id' => $connectedAccountId]),
            'type'        => 'account_onboarding',
        ]);

        return redirect()->away($accountLink->url); // 🔥 send user to finish onboarding
    }

    // If account is fully active
    return response()->json([
        'status' => true,
        'message' => 'Stripe account connected and fully active!',
        'stripe_account_id' => $connectedAccountId,
    ]);
}

    /**
     * Step 3: Refresh onboarding link
     */
    public function accountRefresh($account_id)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $link = AccountLink::create([
                'account'     => $account_id,
                'refresh_url' => route('api.payment.stripe.account.connect.refresh', ['account_id' => $account_id]),
                'return_url'  => route('api.payment.stripe.account.connect.success', ['account_id' => $account_id]),
                'type'        => 'account_onboarding'
            ]);

            return redirect()->away($link->url);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error generating refresh link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 4: Success + refresh endpoints
     */
    public function refresh($account_id)
    {
        return response()->json([
            'status'  => false,
            'message' => "Onboarding for account {$account_id} was interrupted. Please try again."
        ]);
    }

    public function success($account_id)
    {
        return response()->json([
            'status'  => true,
            'message' => "Onboarding for account {$account_id} completed successfully!"
        ]);
    }

    /**
     * Step 5: Login link to Stripe Dashboard (user can edit profile anytime)
     */
    public function createLoginLink()
    {
        $user = auth('api')->user();

        if (!$user || !$user->stripe_account_id) {
            return response()->json(['status' => false, 'message' => 'Stripe account not found.'], 404);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $loginLink = Account::createLoginLink($user->stripe_account_id);

        return response()->json([
            'status' => true,
            'url' => $loginLink->url // 🔥 this opens Stripe Dashboard for user to edit/update profile
        ]);
    }
    // public function accountConnect()
    // {
    //     $user = auth('api')->user();


    //     try {
    //         \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    //         $account = Account::create([
    //             'type' => 'express',
    //             'email' => $user->email,
    //             'capabilities' => [
    //                 'card_payments' => ['requested' => true],
    //                 'transfers' => ['requested' => true],
    //             ],
    //             'settings' => [
    //                 'payouts' => [
    //                     'schedule' => [
    //                         'interval' => 'daily', // daily, weekly, monthly
    //                     ],
    //                 ],
    //             ]
    //         ]);

    //         $link = AccountLink::create([
    //             'account'       => $account->id,
    //             'refresh_url'   => route('api.payment.stripe.account.connect.refresh', ['account_id' => $account->id]),
    //             'return_url'    => route('api.payment.stripe.account.connect.success', ['account_id' => $account->id]),
    //             'type'          => 'account_onboarding'
    //         ]);

    //         $data = [
    //             'url' => $link->url
    //         ];

    //         return response()->json(['status' => 'success', 'data' => $data, 'message' => 'Redirecting to Stripe Express Dashboard..'], 200);
    //     } catch (ApiErrorException $e) {
    //         return response()->json(['status' => 'error', 'message' => 'Stripe API error: ' . $e->getMessage()], 500);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
    //     }
    // }

    // public function accountSuccess($account_id)
    // {

    //     try {
    //         \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));


    //         $account = \Stripe\Account::retrieve($account_id);

    //         $user = User::where('email', $account->email)->first();
    //         if (!$user) {
    //             return response()->json(['status' => 'error', 'message' => 'User not found in the database for this Stripe account.'], 404);
    //         }

    //         $user->update([
    //             'stripe_account_id' => $account_id
    //         ]);
    //         $loginLink = \Stripe\Account::createLoginLink($user->stripe_account_id);
    //         return redirect()->away($loginLink->url);
    //     } catch (Exception $e) {

    //         return response()->json(['status' => 'error', 'message' => 'Error processing onboarding success: ' . $e->getMessage()], 500);
    //     }
    // }

    // public function accountRefresh($account_id)
    // {
    //     try {

    //         $link = AccountLink::create([
    //             'account'       => $account_id,
    //             'refresh_url'   => route('api.payment.stripe.account.connect.refresh', ['account_id' => $account_id]),
    //             'return_url'    => route('api.payment.stripe.account.connect.success', ['account_id' => $account_id]),
    //             'type'          => 'account_onboarding'
    //         ]);

    //         return redirect()->away($link->url);
    //     } catch (Exception $e) {

    //         return response()->json(['status' => 'error', 'message' => 'Error generating refresh link: ' . $e->getMessage()], 500);
    //     }
    // }

    // public function accountUrl()
    // {
    //     $user = auth('api')->user();

    //     if ($user->stripe_account_id) {
    //         try {
    //             $loginLink = Account::createLoginLink($user->stripe_account_id);

    //             $data = [
    //                 'url' => $loginLink->url
    //             ];
    //             return response()->json(['status' => 'success', 'data' => $data, 'message' => 'Redirecting to Stripe Express Dashboard..'], 200);
    //         } catch (Exception $e) {
    //             return response()->json(['status' => 'error', 'message' => 'Error generating Stripe login link: ' . $e->getMessage(),], 500);
    //         }
    //     }
    // }

    // public function accountInfo()
    // {
    //     $user = auth('api')->user();

    //     if ($user->stripe_account_id) {
    //         try {
    //             $account = Account::retrieve($user->stripe_account_id);
    //             /* $balance = Balance::retrieve([], [
    //                 'stripe_account' => $user->stripe_account_id,
    //             ]); */

    //             $data = [
    //                 'account_id' => $account->id,
    //                 'email' => $account->email,
    //                 'payouts_enabled' => $account->payouts_enabled,
    //                 /* 'available_balance' => $balance->available,
    //                 'pending_balance' => $balance->pending, */
    //             ];

    //             return response()->json(['status' => 'success', 'data' => $data, 'message' => 'Account info retrieved successfully.', 'code' => 200], 200);
    //         } catch (Exception $e) {
    //             Log::info($e->getMessage());
    //             return response()->json(['status' => 'error', 'message' => 'Error retrieving account info: ' . $e->getMessage(), 'code' => 500], 500);
    //         }
    //     } else {
    //         return response()->json(['status' => 'error', 'message' => 'User does not have a connected Stripe account.', 'code' => 404], 200);
    //     }
    // }

    // public function withdraw(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'amount'  => 'required|numeric|min:0.01',
    //     ]);

    //     if ($validator->fails()) {
    //         return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
    //     }

    //     try {
    //         $$data = $validator->validated();
    //         $user = auth('api')->user();

    //         if (!$user || !$user->stripe_account_id) {
    //             return Helper::jsonResponse(false, 'User does not have a connected Stripe account.', 404);
    //         }

    //         $account = Account::retrieve($auth->stripe_account_id);
    //         if (!$account) {
    //             return Helper::jsonResponse(false, 'Stripe account not found.', 404);
    //         }

    //         $availableBalance = 0;
    //         $balance = Balance::retrieve(['stripe_account' => $auth->stripe_account_id]);
    //         if (!empty($balance->available) && isset($balance->available[0]->amount)) {
    //             $availableBalance = $balance->available[0]->amount / 100;
    //         }
    //         if ($availableBalance <= 0) {
    //             return Helper::jsonResponse(false, 'You do not have enough balance to withdraw.', 400);
    //         }

    //         if ($validatedData['amount'] >= $availableBalance) {
    //             return Helper::jsonResponse(false, 'You do not have enough balance to withdraw.', 400);
    //         }

    //         Payout::create([
    //             'amount'   => $validatedData['amount'] * 100,
    //             'currency' => 'usd',
    //         ], ['stripe_account' => $auth->stripe_account_id]);

    //         return Helper::jsonResponse(true, 'Withdrawal request sent successfully.', 200);
    //     } catch (ApiErrorException $e) {

    //         return Helper::jsonResponse(false, $e->getMessage(), 400);
    //     } catch (Exception $e) {

    //         return Helper::jsonResponse(false, $e->getMessage(), 400);
    //     }
    // }
}
