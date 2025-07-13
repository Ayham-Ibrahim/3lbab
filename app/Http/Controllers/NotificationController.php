<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendBroadcastNotificationJob;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(15);
        return response()->json($notifications);
    }

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
