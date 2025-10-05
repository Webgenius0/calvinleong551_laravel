<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OfferSendNotification extends Notification
{
    use Queueable;
    
    protected $offer;

    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database']; // You can add 'mail' or 'broadcast' if needed
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'message'    => 'You have received a new offer for your product.',
            'buyer_id'   => $this->offer->buyer_id,
            'buyer_name' => $this->offer->buyer->name,
            'product_id' => $this->offer->product_id,
            'product_name' => $this->offer->product->name,
            'offer_price' => $this->offer->price,
            
        ];
    }
}
