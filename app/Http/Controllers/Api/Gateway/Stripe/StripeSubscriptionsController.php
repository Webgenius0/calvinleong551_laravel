<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Exception;
use Illuminate\Http\Request;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription;
use Illuminate\Support\Facades\DB;
use Stripe\Product;

class StripeSubscriptionsController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }


    /* public function plan(Request $request)
    {
        $validatedData = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'stripe_token'  => 'required',
        ]);

        try {
            DB::beginTransaction();
            $user = auth('api')->user();
            $plan = Plan::findOrFail($validatedData['plan_id']);

            if ($user->stripe_customer_id == null) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $user->update(['stripe_customer_id' => $customer->id]);
                $stripe_customer_id = $customer->id;
            } else {
                $stripe_customer_id = $user->stripe_customer_id;
            }

            if ($user->stripe_subscription_id != null) {
                $subscription = Subscription::retrieve($user->stripe_subscription_id);
                $subscription->cancel();
            }

            $subscription = Subscription::create([
                'customer' => $stripe_customer_id,
                'items' => [
                    [
                        'price' => $plan->stripe_price_id
                    ]
                ],
                'default_payment_method' => $validatedData['stripe_token'],
            ]);

            $user->update([
                'stripe_subscription_id' => $subscription->id,
                'plan_id' => $plan->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription_id' => $subscription->id,
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    } */


    public function plan(Request $request)
    {
        $validatedData = $request->validate([
            'price'         => 'required|numeric',
            'plan_id'       => 'required|exists:plans,id',
            'stripe_token'  => 'required',
        ]);

        try {
            DB::beginTransaction();
            $user = auth('api')->user();
            $plan = Plan::findOrFail($validatedData['plan_id']);

            if ($user->stripe_customer_id == null) {
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $user->update(['stripe_customer_id' => $customer->id]);
                $stripe_customer_id = $customer->id;
            } else {
                $stripe_customer_id = $user->stripe_customer_id;
            }

            if ($user->stripe_subscription_id != null) {
                $subscription = Subscription::retrieve($user->stripe_subscription_id);
                $subscription->cancel();
            }

            //Start Product
            $productRetrieve = Product::all(['active' => true]);

            $filteredProducts = array_filter($productRetrieve->data, function ($product) use ($request) {
                return isset($product->metadata['plan_id']) && $product->metadata['plan_id'] == $request->plan_id;
            });

            if (empty($filteredProducts)) {
                $product = Product::create([
                    'name' => "donation plan {$request->plan_id}",
                    'metadata' => [
                        'plan_id' => $request->plan_id,
                    ],
                ]);
            } else {
                $product = reset($filteredProducts);
            }
            //End Product

            $subscription = Subscription::create([
                'customer' => $stripe_customer_id,
                'items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product' => $product->id,
                        'unit_amount' =>  $validatedData['price'] * 100,
                        'recurring' => ['interval' => 'month'],
                    ],
                ]],
                'default_payment_method' => $validatedData['stripe_token'],
            ]);

            $user->update([
                'stripe_subscription_id' => $subscription->id,
                'plan_id' => $plan->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription_id' => $subscription->id,
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function myPlan()
    {
        try {
            $user = auth('api')->user();
            $userPlan = $user->plan;

            return response()->json([
                'status' => true,
                'message' => 'User Plan',
                'code' => 200,
                'data' => [
                    'plan' => $userPlan
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'code' => 500,
            ], 500);
        }
    }

    public function cancelPlan()
    {
        $user = auth('api')->user();
        $stripe_subcription_id = $user->stripe_subscription_id;
        if (empty($stripe_subcription_id)) {
            return response()->json([
                'status'     => false,
                'message'    => 'Subscription ID not found',
                'code'       => 400,
            ], 400);
        }
        try {
            $subscription = Subscription::retrieve($stripe_subcription_id);
            $user->update([
                'stripe_subscription_id' => null,
                'plan_id' => null
            ]);
            if (!$subscription->status == 'active') {
                return response()->json([
                    'status'     => false,
                    'message'    => 'Subscription is not active',
                    'code'       => 400,
                ], 400);
            }
            $subscription->cancel();
            return response()->json([
                'status'     => true,
                'message'    => 'Subscription Cancelled',
                'code'       => 200,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'     => false,
                'message'    => $e->getMessage(),
                'code'       => 500,
            ], 500);
        }
    }
}
