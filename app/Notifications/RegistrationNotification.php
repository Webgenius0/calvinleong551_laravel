<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RegistrationNotification extends Notification  implements ShouldQueue
{
    use Queueable;

    public $data;
    public function __construct($data)
    {       
        $this->data = $data;
        $user = User::find($data['user_id']);
        $this->data['name'] = $user->name;
        $this->data['email'] = $user->email;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Hello Admin')
            ->line('New user has been registered.')
            ->line('User ID: ' . $this->data['user_id'])
            ->line('Email: ' . $this->data['email'])
            ->line('Name: ' . $this->data['name'])
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->data['user_id'],
            'title' => $this->data['title'],
            'body' => $this->data['body']
        ];
    }

}
