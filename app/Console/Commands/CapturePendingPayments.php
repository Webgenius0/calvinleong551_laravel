<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class CapturePendingPayments extends Command
{
    protected $signature = 'payments:capture-pending';
    protected $description = 'Capture pending payments after 7 days';

    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $pendingPayments = Payment::where('capture_status', 'pending')
            ->where('created_at', '<=', now()->subDays(7))
            ->where('status', 'succeeded')
            ->get();

        foreach ($pendingPayments as $payment) {
            try {
                // Retrieve the PaymentIntent object
                $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_id);
                $paymentIntent->capture(); // <-- instance method call

                // Update payment status
                $payment->update(['capture_status' => 'captured']);

                // Increment seller balance
                $seller = User::find($payment->seller_id);
                $seller->increment('balance', $payment->amount);
            } catch (\Exception $e) {
                Log::error('Stripe capture failed: ' . $e->getMessage());
            }
        }


        $this->info('Pending payments captured successfully.');
    }
}
