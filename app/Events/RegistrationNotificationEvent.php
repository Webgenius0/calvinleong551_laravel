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

class RegistrationNotificationEvent implements ShouldBroadcastNow
{
    
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $user_id;
    public $admin_id;

    public function __construct($data, $admin_id)
    {
        $this->data = $data;
        $this->user_id = $data['user_id'];
        $user = User::find($this->user_id);
        $this->data['name'] = $user->name;
        $this->admin_id = $admin_id;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('notify.'.$this->admin_id)
        ];
    }
    
}
