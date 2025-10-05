<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Exception;
use Stripe\Stripe;
use Stripe\Balance;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\AddToCart;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class StripeWebHookController extends Controller
{
    /**
     * Create Stripe Checkout Session
     */
    // public function createCheckoutSession(Request $request)
    // {
    //     $user = auth()->guard('api')->user();
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorized',
    //         ], 401);
    //     }

    //     $cartId = $request->input('cart_id');

    //     $cartItems = CartItem::with('product.seller')
    //         ->where('add_to_cart_id', $cartId)
    //         ->get();

    //     if ($cartItems->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Cart is empty',
    //         ], 400);
    //     }

    //     // Check sellers' Stripe accounts
    //     $sellerIds = $cartItems->pluck('product.user_id')->unique();
    //     $sellersWithoutStripe = User::whereIn('id', $sellerIds)
    //         ->whereNull('stripe_account_id')
    //         ->get();

    //     if ($sellersWithoutStripe->isNotEmpty()) {
    //         $names = $sellersWithoutStripe->pluck('name')->implode(', ');
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Sellers missing Stripe accounts: ' . $names,
    //         ], 400);
    //     }

    //     // Generate order UID
    //     $uid = '#' . strtoupper(Str::random(5));

    //     // Group items by seller
    //     $sellers = [];
    //     $totalAmount = 0;

    //     foreach ($cartItems as $item) {
    //         $product = $item->product;
    //         if (!$product) continue;

    //         $sellerId = $product->user_id;
    //         $itemTotal = $product->price * $item->quantity;

    //         if (!isset($sellers[$sellerId])) {
    //             $sellers[$sellerId] = [
    //                 'items' => [],
    //                 'total' => 0,
    //                 'stripe_account_id' => $product->seller?->stripe_account_id
    //             ];
    //         }

    //         $sellers[$sellerId]['items'][] = $item;
    //         $sellers[$sellerId]['total'] += $itemTotal;
    //         $totalAmount += $itemTotal;
    //     }

    //     // Create main order (status pending)
    //     $order = Order::create([
    //         'uid' => $uid,
    //         'buyer_id' => $user->id,
    //         'seller_id' => null,
    //         'price' => $totalAmount,
    //         'status' => 'pending',
    //     ]);

    //     Stripe::setApiKey(env('STRIPE_SECRET'));

    //     $checkoutSessions = [];

    //     try {
    //         foreach ($sellers as $sellerId => $data) {
    //             $lineItems = [];
    //             $sellerTotal = $data['total'];
    //             $applicationFee = $sellerTotal * 0.05; // 5% commission
    //             $sellerAmount = $sellerTotal * 0.95; // Seller gets 95%

    //             foreach ($data['items'] as $item) {
    //                 $product = $item->product;

    //                 if (!$product) continue;

    //                 // Create order item
    //                 $orderitem = OrderItem::create([
    //                     'order_id'        => $order->id,
    //                     'product_id'      => $product->id,
    //                     'product_name'    => $product->title ?? null,
    //                     'product_color'   => $item->product_color ?? null,   // from cart_items table
    //                     'product_size'    => $item->product_size ?? null,
    //                     'product_material' => $item->product_material ?? null,
    //                     'product_condition' => $item->product_condition ?? null,
    //                     'seller_id'       => $sellerId,
    //                     'quantity'        => $item->quantity,
    //                     'item_price'      => $product->price,
    //                     'price' => ($product->price ?? 0) * ($item->quantity ?? 1),
    //                     'seller_amount'   => $product->price * $item->quantity * 0.95,
    //                     'admin_amount'    => $product->price * $item->quantity * 0.05,
    //                     'image'           => $item->product_image ?? null,
    //                 ]);

    //                 // Stripe line item
    //                 $lineItems[] = [
    //                     'price_data' => [
    //                         'currency' => 'usd',
    //                         'product_data' => [
    //                             'name' => $product->name ?: "Product #{$product->id}",
    //                             'description' => $product->description ?: 'No description',
    //                         ],
    //                         'unit_amount' => (int) round($product->price * 100),
    //                     ],
    //                     'quantity' => (int) $item->quantity,
    //                 ];
    //             }

    //             // Create Checkout Session for this seller
    //             $session = Session::create([
    //                 'payment_method_types' => ['card'],
    //                 'line_items' => $lineItems,
    //                 'mode' => 'payment',
    //                 'success_url' => 'https://desi-carousel.netlify.app/payment-success?session_id={CHECKOUT_SESSION_ID}',
    //                 'cancel_url' => 'https://desi-carousel.netlify.app/payment-error',
    //                 'metadata' => [
    //                     'order_id' => $order->id,
    //                     'buyer_id' => $user->id,
    //                     'seller_id' => $sellerId,
    //                     'total_amount' => $totalAmount,
    //                     'seller_amount' => $sellerAmount,
    //                     'admin_amount' => $applicationFee,
    //                 ],
    //                 'payment_intent_data' => [
    //                     'application_fee_amount' => (int) round($applicationFee * 100),
    //                     'transfer_data' => [
    //                         'destination' => $data['stripe_account_id'],
    //                     ],
    //                     'capture_method' => 'manual',
    //                 ],
    //             ]);

    //             $checkoutSessions[] = [
    //                 'seller_id' => $sellerId,
    //                 'session_id' => $session->id,
    //                 'checkout_url' => $session->url,
    //             ];
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'checkout_sessions' => $checkoutSessions,
    //             'order_id' => $order->id,
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Stripe Checkout Error: ' . $e->getMessage());
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function createCheckoutSession(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $cartId  = $request->input('cart_id');
        $offerId = $request->input('offer_id');

        if ($cartId && $offerId) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot checkout with both cart_id and offer_id',
            ], 400);
        }

        if ($offerId) {
            $offers = Offer::where('id', $offerId)->where('is_offer', 'accepted')->first();
            if (!$offers) {
                return response()->json([
                    'status' => false,
                    'code'   => 404,
                    'message' => 'Seller doesnot accept your request!',
                ], 404);
            }
            return $this->checkoutFromOffer($user, $offerId);
        }

        if ($cartId) {
            return $this->checkoutFromCart($user, $cartId);
        }

        return response()->json([
            'status' => false,
            'message' => 'Either cart_id or offer_id is required',
        ], 400);
    }

    // checkout form cart

    protected function checkoutFromCart($user, $cartId)
    {
        $cartItems = CartItem::with('product.seller')
            ->where('add_to_cart_id', $cartId)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Cart is empty',
            ], 400);
        }

        // Check sellers' Stripe accounts
        $sellerIds = $cartItems->pluck('product.user_id')->unique();
        $sellersWithoutStripe = User::whereIn('id', $sellerIds)
            ->whereNull('stripe_account_id')
            ->get();

        if ($sellersWithoutStripe->isNotEmpty()) {
            $names = $sellersWithoutStripe->pluck('name')->implode(', ');
            return response()->json([
                'status' => false,
                'message' => 'Sellers missing Stripe accounts: ' . $names,
            ], 400);
        }

        // Generate order UID
        $uid = '#' . strtoupper(Str::random(5));

        // Group items by seller
        $sellers = [];
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product) continue;

            $sellerId = $product->user_id;
            $itemTotal = $product->price * $item->quantity;

            if (!isset($sellers[$sellerId])) {
                $sellers[$sellerId] = [
                    'items' => [],
                    'total' => 0,
                    'stripe_account_id' => $product->seller?->stripe_account_id
                ];
            }

            $sellers[$sellerId]['items'][] = $item;
            $sellers[$sellerId]['total'] += $itemTotal;
            $totalAmount += $itemTotal;
        }

        // Create main order (status pending)
        $order = Order::create([
            'uid' => $uid,
            'buyer_id' => $user->id,
            'seller_id' => null,
            'price' => $totalAmount,
            'status' => 'pending',
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $checkoutSessions = [];

        try {
            foreach ($sellers as $sellerId => $data) {
                $lineItems = [];
                $sellerTotal = $data['total'];
                $applicationFee = $sellerTotal * 0.05; // 5% commission
                $sellerAmount = $sellerTotal * 0.95;   // Seller gets 95%

                foreach ($data['items'] as $item) {
                    $product = $item->product;
                    if (!$product) continue;

                    // Create order item
                    OrderItem::create([
                        'order_id'          => $order->id,
                        'product_id'        => $product->id,
                        'product_name'      => $product->title ?? null,
                        'product_color'     => $item->product_color ?? null,
                        'product_size'      => $item->product_size ?? null,
                        'product_material'  => $item->product_material ?? null,
                        'product_condition' => $item->product_condition ?? null,
                        'seller_id'         => $sellerId,
                        'quantity'          => $item->quantity,
                        'item_price'        => $product->price,
                        'price'             => ($product->price ?? 0) * ($item->quantity ?? 1),
                        'seller_amount'     => $product->price * $item->quantity * 0.95,
                        'admin_amount'      => $product->price * $item->quantity * 0.05,
                        'image'             => $item->product_image ?? null,
                    ]);

                    // Stripe line item
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $product->name ?: "Product #{$product->id}",
                                'description' => $product->description ?: 'No description',
                            ],
                            'unit_amount' => (int) round($product->price * 100),
                        ],
                        'quantity' => (int) $item->quantity,
                    ];
                }

                // Create Checkout Session for this seller
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => 'https://desi-carousel.netlify.app/payment-success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => 'https://desi-carousel.netlify.app/payment-error',
                    'metadata' => [
                        'order_id'      => $order->id,
                        'buyer_id'      => $user->id,
                        'seller_id'     => $sellerId,
                        'total_amount'  => $totalAmount,
                        'seller_amount' => $sellerAmount,
                        'admin_amount'  => $applicationFee,
                        'source'        => 'cart', // 🔑 mark source = cart
                    ],
                    'payment_intent_data' => [
                        'application_fee_amount' => (int) round($applicationFee * 100),
                        'transfer_data' => [
                            'destination' => $data['stripe_account_id'],
                        ],
                        'capture_method' => 'manual',
                    ],
                ]);

                $checkoutSessions[] = [
                    'seller_id'    => $sellerId,
                    'session_id'   => $session->id,
                    'checkout_url' => $session->url,
                ];
            }

            return response()->json([
                'status' => true,
                'checkout_sessions' => $checkoutSessions,
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Cart Checkout Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Handle Stripe Webhook
     */
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_CHECKOUT_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->processCheckoutSession($session);
                break;

            default:
                Log::info('Unhandled webhook: ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Process completed Checkout Session
     */
    // protected function processCheckoutSession($session)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $orderId = $session->metadata->order_id;
    //         $buyerId = $session->metadata->buyer_id;
    //         $sellerId = $session->metadata->seller_id;
    //         $sellerAmount = $session->metadata->seller_amount;
    //         $adminAmount = $session->metadata->admin_amount;
    //         $totalAmount = $session->metadata->total_amount;

    //         $order = Order::find($orderId);
    //         if (!$order) throw new Exception("Order not found: {$orderId}");

    //         $seller = User::find($sellerId);
    //         if (!$seller || !$seller->stripe_account_id) throw new Exception("Seller not found or missing Stripe account: {$sellerId}");

    //         // Create payment record for this seller
    //         $payments = Payment::create([
    //             'order_id' => $order->id,
    //             'buyer_id' => $buyerId,
    //             'seller_id' => $sellerId,
    //             // 'stripe_payment_id' => $session->id,
    //             'stripe_payment_id' => $session->payment_intent,
    //             'stripe_account_id' => $seller->stripe_account_id,
    //             'amount' => $totalAmount,
    //             'seller_amount' => $sellerAmount,
    //             'currency' => 'usd',
    //             'status' => 'succeeded',
    //             'capture_status' => 'pending', // 7-day hold
    //             'payment_method' => 'card',
    //         ]);

    //         $cart = AddToCart::where('user_id', $session->metadata->seller_id)->first();
    //         if ($payments->status === 'succeeded') {
    //             $cartItems = AddToCart::with('cartItem')
    //                 ->where('user_id', $buyerId)
    //                 ->get();

    //             foreach ($cartItems as $cart) {
    //                 foreach ($cart->cartItem as $item) { // Loop through cartItems
    //                     if ($item && $item->product_id && $item->quantity) {
    //                         $product = Product::find($item->product_id);

    //                         if ($product) {
    //                             $product->decrement('quantity', $item->quantity);
    //                         }
    //                     }
    //                 }

    //                 // Delete the entire cart entry after processing items
    //                 $cart->delete();
    //             }
    //         }

    //         // Update seller balance
    //         $seller->increment('balance', $sellerAmount);
    //         // Check if all sessions for the order are completed
    //         $pendingItems = OrderItem::where('order_id', $order->id)
    //             ->whereNotIn('seller_id', function ($query) use ($orderId) {
    //                 $query->select('seller_id')
    //                     ->from('payments')
    //                     ->where('order_id', $orderId)
    //                     ->where('status', 'succeeded');
    //             })
    //             ->exists();

    //         if (!$pendingItems) {
    //             $order->update(['status' => 'completed']);
    //         }

    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Stripe processCheckoutSession failed: ' . $e->getMessage());
    //     }
    // }
    protected function processCheckoutSession($session)
    {
        DB::beginTransaction();

        try {
            $orderId = $session->metadata->order_id;
            $buyerId = $session->metadata->buyer_id;
            $sellerId = $session->metadata->seller_id;
            $sellerAmount = $session->metadata->seller_amount;
            $adminAmount = $session->metadata->admin_amount;
            $totalAmount = $session->metadata->total_amount;

            $order = Order::find($orderId);
            if (!$order) throw new Exception("Order not found: {$orderId}");

            $seller = User::find($sellerId);
            if (!$seller || !$seller->stripe_account_id) throw new Exception("Seller not found or missing Stripe account: {$sellerId}");

            // Create payment record
            $payments = Payment::create([
                'order_id' => $order->id,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'stripe_payment_id' => $session->payment_intent,
                'stripe_account_id' => $seller->stripe_account_id,
                'amount' => $totalAmount,
                'seller_amount' => $sellerAmount,
                'currency' => 'usd',
                'status' => 'succeeded',
                'capture_status' => 'pending',
                'payment_method' => 'card',
            ]);

            if ($payments->status === 'succeeded') {

                // ===== Process cart items =====
                $cartItems = AddToCart::with('cartItem')->where('user_id', $buyerId)->get();

                foreach ($cartItems as $cart) {
                    foreach ($cart->cartItem as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->decrement('quantity', $item->quantity);
                            $product->increment('sell_count', $item->quantity);
                        }

                        // Delete any related offers for this buyer and product
                        Offer::where('buyer_id', $buyerId)
                            ->where('product_id', $item->product_id)
                            ->delete();
                    }

                    // Delete the cart entry
                    $cart->delete();
                }

                // ===== Process sold offer items =====
                $soldOffers = Offer::where('buyer_id', $buyerId)
                    ->where('seller_id', $sellerId)
                    ->where('is_offer', 'accepted') // only accepted offers
                    ->get();

                foreach ($soldOffers as $offer) {
                    $product = Product::find($offer->product_id);
                    if ($product) {
                        $product->decrement('quantity', 1); // assuming 1 quantity per offer
                    }

                    // Delete the offer after processing
                    $offer->delete();
                }
            }

            // Update seller balance
            $seller->increment('balance', $sellerAmount);

            // Check if all sessions for the order are completed
            $pendingItems = OrderItem::where('order_id', $order->id)
                ->whereNotIn('seller_id', function ($query) use ($orderId) {
                    $query->select('seller_id')
                        ->from('payments')
                        ->where('order_id', $orderId)
                        ->where('status', 'succeeded');
                })
                ->exists();

            if (!$pendingItems) {
                $order->update(['status' => 'completed']);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Stripe processCheckoutSession failed: ' . $e->getMessage());
        }
    }



    // offer checkout 

    protected function checkoutFromOffer($user, $offerId)
    {
        $offer = Offer::with('product.seller')->find($offerId);

        if (!$offer) {
            return response()->json([
                'status' => false,
                'message' => 'Offer not found',
            ], 404);
        }

        $seller = $offer->product->seller;
        if (!$seller || !$seller->stripe_account_id) {
            return response()->json([
                'status' => false,
                'message' => 'Seller missing Stripe account',
            ], 400);
        }

        $uid = '#' . strtoupper(Str::random(5));
        $totalAmount = $offer->offer_price;
        $applicationFee = $totalAmount * 0.05;
        $sellerAmount   = $totalAmount * 0.95;

        // Create order
        $order = Order::create([
            'uid'       => $uid,
            'buyer_id'  => $user->id,
            'seller_id' => $seller->id,
            'price'     => $totalAmount,
            'status'    => 'pending',
        ]);

        // Create order item
        OrderItem::create([
            'order_id'          => $order->id,
            'product_id'        => $offer->product_id,
            'product_name'      => $offer->product->title ?? null,
            'product_color'     => $offer->selected_color,
            'product_size'     => $offer->selected_size,
            'quantity'          => 1,
            'item_price'        => $offer->offer_price,
            'price'             => $offer->offer_price,
            'seller_id'         => $seller->id,
            'seller_amount'     => $sellerAmount,
            'admin_amount'      => $applicationFee,
            'image'             => $offer->product->image ?? null,
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $offer->product->title,
                            'description' => $offer->product->description ?? 'No description',
                        ],
                        'unit_amount' => (int) round($offer->offer_price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => 'https://desi-carousel.netlify.app/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://desi-carousel.netlify.app/payment-error',
                'metadata' => [
                    'order_id'      => $order->id,
                    'buyer_id'      => $user->id,
                    'seller_id'     => $seller->id,
                    'total_amount'  => $totalAmount,
                    'seller_amount' => $sellerAmount,
                    'admin_amount'  => $applicationFee,
                    'source'        => 'offer',
                ],
                'payment_intent_data' => [
                    'application_fee_amount' => (int) round($applicationFee * 100),
                    'transfer_data' => [
                        'destination' => $seller->stripe_account_id,
                    ],
                    'capture_method' => 'manual',
                ],
            ]);

            return response()->json([
                'status' => true,
                'checkout_sessions' => [[
                    'seller_id' => $seller->id,
                    'session_id' => $session->id,
                    'checkout_url' => $session->url,
                ]],
                'order_id' => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Offer Checkout Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getSellerBalance()
    {
        $user = auth('api')->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'status' => true,
                'message' => 'User Balance retrieved successfully.',
                'user_name' => $user->name ?? $user->full_name,
                'user_cover' => $user->avatar ? url($user->avatar) : null,
                'is_connect_stripe' => false,
                'available_balance' => 0,
                'pending_balance' => 0,
            ], 200);
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $balance = \Stripe\Balance::retrieve(['stripe_account' => $user->stripe_account_id]);

            $available = $balance->available[0]->amount / 100;
            $pending = $balance->pending[0]->amount / 100;

            return response()->json([
                'status' => true,
                'message' => 'User Balance retrieved successfully.',
                'is_connect_stripe' => true,
                'available_balance' => $available,
                'pending_balance' => $pending,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
