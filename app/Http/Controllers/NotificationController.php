<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendBroadcastNotificationJob;

class NotificationController extends Controller
{
    public function sendAdminBroadcastNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        SendBroadcastNotificationJob::dispatch(
            $request->title,
            $request->body,
            [] 
        );

        return response()->json([
            'message' => 'تم جدولة الإشعار ليُرسل إلى جميع المستخدمين.',
        ], 200);    }
}
