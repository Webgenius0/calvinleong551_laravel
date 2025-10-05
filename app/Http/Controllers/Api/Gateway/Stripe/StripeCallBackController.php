<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StripeCallBackController extends Controller
{
    public $redirectFail;
    public $redirectSuccess;

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $this->redirectFail = env("APP_URL") . "/fail";
        $this->redirectSuccess = env("APP_URL") . "/success";
    }

    public function checkout(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        try {

            $data = $validator->validated();
            $uid = Str::uuid();

            $successUrl = route('payment.stripe.success') . '?token={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('payment.stripe.cancel') . '?token={CHECKOUT_SESSION_ID}';

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'donation'
                        ],
                        'unit_amount' => $data['price'] * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'order_id' => $uid,
                    'user_id' => auth('api')->user()->id
                ],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            $data = [
                'checkout_url' => $session->url
            ];

            return Helper::jsonResponse(true, 'Checkout session created successfully', 200, $data);
        } catch (ModelNotFoundException $e) {

            Log::error($e->getMessage());
            return redirect()->to($this->redirectFail);
        } catch (ApiErrorException $e) {

            Log::error($e->getMessage());
            return redirect()->to($this->redirectFail);
        }
    }

    public function success(Request $request)
    {
        $validatedData = $request->validate([
            'token' => ['required', 'string']
        ]);

        try {

            $session = Session::retrieve($validatedData['token']);
            if ($session->payment_status === 'paid') {

                Transaction::create([
                    'user_id'   => $session->metadata['user_id'],
                    'amount'    => $session->amount_total / 100,
                    'currency'  => $session->currency,
                    'trx_id'    => $session->id,
                    'type'      => 'increment',
                    'status'    => 'success',
                    'metadata'  => json_encode($session->metadata)
                ]);

                return redirect()->to($this->redirectSuccess);
            }

            if ($session->payment_status === 'unpaid' || $session->payment_status === 'no_payment_required') {
                return redirect()->to($this->redirectFail);
            }

            return redirect()->to($this->redirectFail);
        } catch (ApiErrorException $e) {

            Log::error($e->getMessage());
            return redirect()->to($this->redirectFail);
        } catch (ModelNotFoundException $e) {

            Log::error($e->getMessage());
            return redirect()->to($this->redirectFail);
        }
    }


    public function failure(Request $request)
    {
        return redirect()->to($this->redirectFail);
    }
}
