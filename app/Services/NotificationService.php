<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // Priorities
    const PRIORITY_LOW      = 'low';
    const PRIORITY_MEDIUM   = 'medium';
    const PRIORITY_HIGH     = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Categories for grouping
    const CATEGORY_ORDER   = 'order';
    const CATEGORY_SYSTEM  = 'system';
    const CATEGORY_SUPPORT = 'support';
    const CATEGORY_AUTH    = 'auth';

    /**
     * Send notification to a specific user
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
        $priority = self::PRIORITY_MEDIUM,
        $link = null,
        $groupId = null
    ) {
        try {
            // Check for duplicates/grouping if groupId is provided
            if ($groupId) {
                $existing = Notification::where('user_id', $userId)
                    ->where('group_id', $groupId)
                    ->where('is_read', false)
                    ->where('created_at', '>', now()->subHours(1))
                    ->first();

                if ($existing) {
                    $existing->update([
                        'message' => $message,
                        'data' => array_merge($existing->data ?? [], $data)
                    ]);
                    return $existing;
                }
            }

            $notification = Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $data,
                'priority' => $priority,
                'link'    => $link,
                'group_id' => $groupId,
                'is_read' => false,
            ]);

            // Real-time broadcast
            self::broadcastNotification($notification);

            // Clear unread count cache
            \Cache::forget("notif_count_{$userId}");

            return $notification;
        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage());
            return null;
        }
    }

    public static function sendToAdmins(
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        $admins = User::whereHas('roles', fn($q) =>
            $q->where('name', 'admin')
        )->get();
 
        foreach ($admins as $admin) {
            self::send($admin->id, $type, $title, $message, $data, self::PRIORITY_HIGH);
        }
    }
 
    public static function notifyAdmins(
        string $title,
        string $message,
        string $type,
        $priority = self::PRIORITY_MEDIUM,
        array $data = [],
        $link = null
    ): void {
        $admins = User::role('admin')->get();
 
        foreach ($admins as $admin) {
            self::send($admin->id, $type, $title, $message, $data, $priority, $link);
        }
    }

    public static function sendToSellers(
        int $orderId,
        string $title,
        string $message,
        array $data = []
    ): void {
        // Find sellers who have products in this order
        $orderItems = \App\Models\OrderItem::with('product')
            ->where('order_id', $orderId)->get();

        $sellerIds = $orderItems
            ->pluck('product.seller_id')
            ->filter()
            ->unique();

        foreach ($sellerIds as $sellerId) {
            self::send($sellerId, 'new_order', $title, $message, $data, self::PRIORITY_MEDIUM);
        }
    }

    /**
     * Send notification to all users with a specific role
     */
    public static function sendToRole($role, $type, $title, $message, $priority = self::PRIORITY_MEDIUM, $data = [], $link = null)
    {
        $users = User::role($role)->get();
        foreach ($users as $user) {
            self::send($user->id, $type, $title, $message, $data, $priority, $link);
        }
    }

    public static function notifySupport($title, $message, $type, $priority = self::PRIORITY_MEDIUM, $data = [], $link = null)
    {
        self::sendToRole('support', $type, $title, $message, $priority, $data, $link);
    }

    public static function broadcastNotification($notification)
    {
        // Integration with Pusher/Socket.io could go here
        // For now we log it as per BROADCAST_DRIVER=log
        try {
            event(new \App\Events\NotificationSent($notification));
        } catch (\Exception $e) {
            Log::warning("Broadcasting failed: " . $e->getMessage());
        }
    }

    public static function getDefaultPreferences()
    {
        return [
            'order_updates'   => true,
            'promotions'      => true,
            'system_alerts'   => true,
            'support_tickets' => true,
        ];
    }
}
