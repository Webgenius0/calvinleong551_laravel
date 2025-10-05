<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestNotificationEvent implements ShouldBroadcastNow
{
    
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $user_id;

    public function __construct($data, $user_id)
    {
        $this->data = $data;
        $this->user_id = $user_id;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('test-notify.'.$this->user_id)
        ];
    }
    
}
