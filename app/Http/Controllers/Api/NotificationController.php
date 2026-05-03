<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/user/notifications
     */
    public function index(Request $request)
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * GET /api/user/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['unread_count' => 0]);

            $count = \Illuminate\Support\Facades\DB::table('notifications')
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return response()->json(['unread_count' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['unread_count' => 0]);
        }
    }

    /**
     * PUT /api/user/notifications/{id}/read
     */
    public function markRead(Request $request, $id)
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * PUT /api/user/notifications/read-all
     */
    public function markAllRead(Request $request)
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * DELETE /api/user/notifications/{id}
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
