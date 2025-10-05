<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use UnexpectedValueException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StripeWebHookHoldController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }
    public function createPaymentHold(Request $request)
    {

        try {

            $paymentIntent = PaymentIntent::create([
                'amount' => 1000,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'capture_method' => 'manual',
                'description' => 'Order #123',
                'metadata' => [
                    'order_id' => '123',
                ],
            ]);

            $order = Order::create([
                'amount' => 10.00,
                'stripe_payment_id' => $paymentIntent->id,
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'order_id' => $order->id,
            ]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function capturePayment(Request $request)
    {

        try {
            $orderId = $request->order_id;
            $order = Order::findOrFail($orderId);

            if ($order->payment_status !== 'authorized') {
                return response()->json(['error' => 'Payment not authorized yet'], 400);
            }

            // Capture the payment
            $paymentIntent = PaymentIntent::retrieve($order->stripe_payment_id);
            $paymentIntent->capture([
                // You can capture a different amount than what was authorized
                // 'amount_to_capture' => 900, // Optional: capture a smaller amount (e.g. $9.00)
            ]);

            // Update order status
            $order->update([
                'payment_status' => 'processing_capture',
            ]);

            return response()->json(['status' => 'success', 'message' => 'Payment capture initiated']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelPaymentHold(Request $request)
    {
        // Set your Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $orderId = $request->order_id;
            $order = Order::findOrFail($orderId);

            if ($order->payment_status !== 'authorized') {
                return response()->json(['error' => 'Payment not authorized or already captured'], 400);
            }

            // Cancel the payment intent
            $paymentIntent = PaymentIntent::retrieve($order->stripe_payment_id);
            $paymentIntent->cancel();

            // Update order status
            $order->update([
                'payment_status' => 'processing_cancellation',
            ]);

            return response()->json(['status' => 'success', 'message' => 'Payment cancellation initiated']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}
