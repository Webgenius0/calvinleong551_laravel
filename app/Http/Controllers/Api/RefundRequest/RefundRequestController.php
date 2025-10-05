<?php

namespace App\Http\Controllers\Api\RefundRequest;

use Stripe\Refund;
use Stripe\Stripe;
use App\Models\User;
use App\Models\Payment;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\SellerRefundRequestResource;

class RefundRequestController extends Controller
{
    public function refundRequest(Request $request)
    {

        // Validate input
        $validator = Validator::make($request->all(), [
            'order_item_id' => 'required|exists:order_items,id',
            'reason'        => 'nullable|string|max:1000',
            'image'         => 'nullable|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $orderItem = OrderItem::with('order.payment')->find($request->order_item_id);

        if (!$orderItem) {
            return response()->json(['status' => false, 'message' => 'Order item not found'], 404);
        }

        // Ensure buyer owns this order
        if ($orderItem->order->buyer_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'You cannot request refund for this item'], 403);
        }

        // Ensure payment succeeded
        if ($orderItem->order->payment->status !== 'succeeded') {
            return response()->json(['status' => false, 'message' => 'Payment not successful'], 422);
        }

        // Prevent duplicate pending refund
        if (RefundRequest::where('order_item_id', $orderItem->id)->where('status', 'pending')->exists()) {
            return response()->json(['status' => false, 'message' => 'Refund already requested for this product'], 409);
        }

        // Handle optional image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $destinationPath = public_path('uploads/refund_images');
            if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);
            $filename = 'refund_' . $orderItem->id . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/refund_images/' . $filename;
        }

        // Create refund request
        $refund = RefundRequest::create([
            'payment_id'    => $orderItem->order->payment->id,
            'order_id'      => $orderItem->order->id,
            'order_item_id' => $orderItem->id,
            'buyer_id'      => $user->id,
            'buyer_name'    => $user->name ?? null,
            'seller_id'     => $orderItem->seller_id,
            'reason'        => $request->reason,
            'image'         => $imagePath,
            'status'        => 'pending',
        ]);

        if ($refund) {
            OrderItem::where('id', $refund->order_item_id)->update([
                'refund_status' => 'pending',
            ]);
        }


        return response()->json([
            'status'  => true,
            'message' => 'Refund request submitted successfully',
            'refund'  => $refund,
        ], 201);
    }

    // get seller products refund request

    public function getRefundRequest()
    {
        $autUser = auth()->guard('api')->user();
        if (!$autUser) {
            return response()->json([
                'status' => false,
                'code'   => 401,
                'message' => 'Unauthorize'
            ]);
        }

        $refundRequest = RefundRequest::with('orderItmes')->where('seller_id', $autUser->id)->orderBy('created_at', 'desc')->get();
        if (!$refundRequest) {
            return response()->json([
                'status'   => false,
                'code'     => 404,
                'message'  => 'Not found'
            ]);
        }

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'data' => SellerRefundRequestResource::collection($refundRequest),
        ]);
    }


    /**
     * Admin approve/reject endpoint
     * On approve you may call Stripe refund/cancel logic (not included here)
     */
    // public function approveRefund(Request $request)
    // {
    //     $user = auth()->guard('api')->user(); // Admin
    //     if (!$user) {
    //         return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    //     }

    //     $refundId = $request->query('refundId');
    //     $status   = $request->query('status', 'approved'); // approved বা rejected

    //     $refund = RefundRequest::with('orderItmes.order.payment')->find($refundId);
    //     if (!$refund) {
    //         return response()->json(['status' => false, 'message' => 'Refund request not found'], 404);
    //     }

    //     if ($refund->buyer_id == $user->id) {
    //         return response()->json([
    //             'status' => false,
    //             'code' => 401,
    //             'message' => 'Buyer cannot approve his own refund request'
    //         ]);
    //     }

    //     if ($refund->status !== 'pending') {
    //         return response()->json(['status' => false, 'message' => 'Refund already processed'], 409);
    //     }

    //     $orderItem = $refund->orderItmes;
    //     $payment   = $orderItem->order->payment ?? null;
    //     $buyerId   = $refund->buyer_id;
    //     $sellerId  = $refund->seller_id;

    //     if (!$orderItem || !$payment || !$buyerId || !$sellerId) {
    //         return response()->json(['status' => false, 'message' => 'Invalid refund data'], 422);
    //     }

    //     try {
    //         \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    //         if ($status === 'approved') {
    //             $pi = \Stripe\PaymentIntent::retrieve($payment->stripe_payment_id);

    //             if ($pi->status === 'requires_capture') {
    //                 // Payment uncaptured → capture first, then refund
    //                 $capturedPayment = $pi->capture();
    //                 $refundStripe = \Stripe\Refund::create([
    //                     'payment_intent' => $capturedPayment->id,
    //                     'amount' => (int) ($orderItem->price * 100),
    //                 ]);
    //             } elseif ($pi->status === 'succeeded') {
    //                 // Already captured → normal refund
    //                 $refundStripe = \Stripe\Refund::create([
    //                     'payment_intent' => $pi->id,
    //                     'amount' => (int) ($orderItem->price * 100),
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => 'Payment is not eligible for refund'
    //                 ], 422);
    //             }

    //             // Deduct seller balance
    //             $seller = User::find($sellerId);
    //             if ($seller) {
    //                 $sellerDeduction = $orderItem->seller_amount ?? ($orderItem->price * 0.95);
    //                 $seller->decrement('balance', $sellerDeduction);
    //             }

    //             // Update refund record
    //             $refund->update([
    //                 'status'           => 'approved',
    //                 'refund_stripe_id' => $refundStripe->id,
    //                 'refund_amount'    => $orderItem->price,
    //                 'refunded_at'      => now(),
    //             ]);

    //             // Update payment & order item
    //             $payment->update([
    //                 'status'        => 'refunded',
    //                 'refund_status' => 'completed',
    //             ]);

    //             $orderItem->update([
    //                 'refund_status' => 'refunded',
    //             ]);

    //             return response()->json([
    //                 'status'  => true,
    //                 'message' => 'Refund approved and processed successfully',
    //                 'refund'  => $refund,
    //             ]);
    //         } else {
    //             // Rejected case
    //             $refund->update(['status' => 'rejected']);
    //             return response()->json([
    //                 'status'  => true,
    //                 'message' => 'Refund request rejected',
    //                 'refund'  => $refund,
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Refund processing failed: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function processRefund(Request $request)
    {
        $request->validate([
            'refund_request_id' => 'required|exists:refund_requests,id',
            'status' => 'required|in:approved,rejected'
        ]);

        $admin  = auth()->guard('api')->user();
        $refund = RefundRequest::with('orderItem.order.payment', 'orderItem.seller')
            ->findOrFail($request->refund_request_id);

        if ($refund->status != 'pending') {
            return response()->json([
                'status'  => false,
                'message' => 'Refund already processed'
            ]);
        }

        $orderItem = $refund->orderItem;
        $payment   = $orderItem->order->payment;

        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $pi = \Stripe\PaymentIntent::retrieve($payment->stripe_payment_id);

            // Convert item price into cents
            $refundAmount = (int) ($orderItem->price * 100);

            if ($request->status == 'approved') {

                if ($pi->status === 'requires_capture') {
                    // 1️⃣ First capture full payment
                    $pi->capture();

                    // 2️⃣ Then refund only this item's amount
                  $stripeRefund =  \Stripe\Refund::create([
                        'payment_intent' => $pi->id,
                        'amount' => $refundAmount,
                    ]);
                } else {
                    // Already captured → refund directly
                   $stripeRefund = \Stripe\Refund::create([
                        'payment_intent' => $pi->id,
                        'amount' => $refundAmount,
                    ]);
                }

                // Seller deduction
                $seller           = $orderItem->seller;
                $sellerDeduction  = $orderItem->seller_amount ?? ($orderItem->price * 0.95);

                if ($seller) {
                    $seller->decrement('balance', $sellerDeduction);
                }

                // Update refund request
                $refund->update([
                    'status'          => 'approved',
                    'refund_id'        => $stripeRefund->id,
                    'refund_amount'   => $orderItem->price,
                    'stripe_fee'      => $orderItem->admin_amount,
                    'seller_deduction' => $orderItem->seller_amount,
                    'admin_loss'      => $orderItem->admin_amount,
                    'refunded_at'     => now(),
                ]);

                // Update order item
                $orderItem->update(['refund_status' => 'refunded']);

                return response()->json([
                    'status'  => true,
                    'message' => 'Refund approved and processed',
                    'data'    => $refund
                ]);
            }

            if ($request->status == 'rejected') {
                $refund->update([
                    'status'      => 'rejected',
                    'refunded_at' => now()
                ]);

                return response()->json([
                    'status'  => true,
                    'message' => 'Refund rejected',
                    'data'    => $refund
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => "Refund processing failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List refund requests - for admin
     */
    public function index()
    {
        $refunds = RefundRequest::with(['payment', 'buyer', 'seller'])->latest()->get();

        // add image_url
        $refunds = $refunds->map(function ($r) {
            $arr = $r->toArray();
            $arr['image_url'] = $r->image_url;
            return $arr;
        });

        return response()->json(['status' => true, 'refunds' => $refunds]);
    }
}
