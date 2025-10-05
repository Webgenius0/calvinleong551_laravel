<?php

namespace App\Helpers;

use App\Models\User;
use App\Events\TestNotificationEvent;
use App\Notifications\TestNotification;
use App\Mail\TestMail;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notify
{

    public static function Firebase(string $title, string $body, $user_id)
    {
        $user = User::find($user_id);
        if ($user && $user->firebaseTokens) {
            $notifyData = ['title' => $title, 'body'  => $body, 'icon'  => env('APP_LOGO')];
            foreach ($user->firebaseTokens as $firebaseToken) {
                Helper::sendNotifyMobile($firebaseToken->token, $notifyData);
            }
        }
    }

    public static function inApp(string $title, string $body, $user_id = null)
    {
        $user = $user_id ? User::find($user_id) : User::role('admin', 'web')->first();

        $notiData = [
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'icon'  => env('APP_LOGO')
        ];

        $user->notify(new TestNotification($notiData, $user->id));
        broadcast(new TestNotificationEvent($notiData, $user->id))->toOthers();
    }

    public static function email($subject, $content, $user_id = null)
    {
        try {
            $user = $user_id ? User::find($user_id) : User::role('admin', 'web')->first();
            Mail::to($user->email)->send(new TestMail($subject, $content));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
