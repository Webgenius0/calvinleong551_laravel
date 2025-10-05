<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OfferStatusUpdated extends Notification
{
    use Queueable;

    protected $status;
    protected $offerId;

    public function __construct($status, $offerId)
    {
        $this->status = $status;
        $this->offerId = $offerId;
    }

    public function via($notifiable)
    {
        return ['database']; // saves in notifications table
    }

    public function toDatabase($notifiable)
    {
        $message = $this->status === 'accepted'
            ? 'Your offer has been accepted! You can buy the product now.'
            : 'Your offer was rejected. You can buy at the original price.';

        return [
            'offer_id' => $this->offerId,
            'status'   => $this->status,
            'message'  => $message,
        ];
    }
}
