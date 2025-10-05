<?php

namespace App\Services;

use App\Models\Transaction;

class StripeService
{
    public function success($paymentIntent): void
    {
        Transaction::create([
            'user_id'   => $paymentIntent->metadata->user_id,
            'amount'    => $paymentIntent->amount / 100,
            'currency'  => $paymentIntent->currency,
            'trx_id'    => $paymentIntent->id,
            'type'      => 'increment',
            'status'    => 'success',
            'metadata'  => json_encode($paymentIntent->metadata)
        ]);
    }

    public function failure($paymentIntent): void
    {
        //? Handle payment failure    
    }
}
