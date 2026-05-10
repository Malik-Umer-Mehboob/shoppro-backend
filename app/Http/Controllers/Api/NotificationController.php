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
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => ['count' => 0]
            ]);
        }

        $count = \Cache::remember(
            "notif_count_{$user->id}", 30,
            fn() => \DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type',
                    'App\\Models\\User')
                ->whereNull('read_at')
                ->count()
        );

        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }

    /**
     * PUT /api/user/notifications/{id}/read
     */
    public function markRead(Request $request, $id)
    {
        $notification = Notification::forUser($request->user()->id)->findOrFail($id);
        $notification->markAsRead();

        \Cache::forget("notif_count_{$request->user()->id}");

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * PUT /api/user/notifications/read-all
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);

        \Cache::forget("notif_count_{$user->id}");

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
