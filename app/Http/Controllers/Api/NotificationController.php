<?php

namespace App\Http\Controllers\Api;

use App\Events\TestNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TestNotification;
use Exception;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function test(){

        $user = auth('api')->user();
        $admin = User::role('admin', 'web')->first();

        $notiData = [
            'user_id' => $user->id,
            'title' => 'Test Notification Title.',
            'body' => 'Your Test Notification Body.',
            'icon'  => config('settings.logo')
        ];

        $admin->notify(new TestNotification($notiData, $admin->id));
        $user->notify(new TestNotification($notiData, $user->id));
        
        if(config('settings.reverb') == 'on'){
            broadcast(new TestNotificationEvent($notiData, $admin->id))->toOthers();
        }

        return true;
    }

    public function index()
    {
        try {
            $user = auth('api')->user();
            $notifications = $user->unreadNotifications;
            return response()->json([
                'status'     => true,
                'message'    => 'All Notifications',
                'code'       => 200,
                'data'       => $notifications,
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return back();
        }
    }
    public function readSingle($id)
    {
        try {
            $notification = auth('api')->user()->notifications()->find($id);
            if($notification) {
                $notification->markAsRead();
            }
            return response()->json([
                'status'     => true,
                'message'    => 'Single Notification',
                'code'       => 200,
                'data'       => $notification
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return back();
        }
    }
    public function readAll()
    {
        try {
            auth('api')->user()->notifications->markAsRead();
            return response()->json([
                'status'     => true,
                'message'    => 'All Notifications Marked As Read',
                'code'       => 200,
                'data'       => null
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return back();
        }
    }

}
