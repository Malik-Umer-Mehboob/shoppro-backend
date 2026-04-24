<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;

class NotificationService
{
    /**
     * Create an in-app notification for a user.
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'data'    => $data,
        ]);
    }

    /**
     * Send order-related notification.
     */
    public function sendOrderNotification(Order $order, string $type): Notification
    {
        $titles = [
            Notification::TYPE_ORDER_PLACED    => 'Order Confirmed!',
            Notification::TYPE_ORDER_SHIPPED   => 'Your Order Has Been Shipped',
            Notification::TYPE_ORDER_DELIVERED  => 'Order Delivered Successfully',
            Notification::TYPE_ORDER_CANCELLED => 'Order Cancelled',
            Notification::TYPE_ORDER_REFUNDED  => 'Refund Processed',
        ];

        $messages = [
            Notification::TYPE_ORDER_PLACED    => "Your order #{$order->id} has been placed successfully. Total: PKR " . number_format($order->grand_total, 2),
            Notification::TYPE_ORDER_SHIPPED   => "Your order #{$order->id} is on its way!" . ($order->tracking_number ? " Tracking: {$order->tracking_number}" : ''),
            Notification::TYPE_ORDER_DELIVERED  => "Your order #{$order->id} has been delivered. Enjoy your purchase!",
            Notification::TYPE_ORDER_CANCELLED => "Your order #{$order->id} has been cancelled.",
            Notification::TYPE_ORDER_REFUNDED  => "A refund of PKR " . number_format($order->grand_total, 2) . " has been processed for order #{$order->id}.",
        ];

        return $this->createNotification(
            $order->customer,
            $type,
            $titles[$type] ?? 'Order Update',
            $messages[$type] ?? "Your order #{$order->id} has been updated.",
            "/orders/{$order->id}",
            ['order_id' => $order->id, 'status' => $order->status]
        );
    }

    /**
     * Notify seller about a new order.
     */
    public function notifySellerNewOrder(Order $order): ?Notification
    {
        if (!$order->seller) return null;

        return $this->createNotification(
            $order->seller,
            Notification::TYPE_NEW_ORDER,
            'New Order Received!',
            "You have a new order #{$order->id} worth PKR " . number_format($order->grand_total, 2),
            "/orders/{$order->id}",
            ['order_id' => $order->id]
        );
    }

    /**
     * Send welcome notification.
     */
    public function sendWelcomeNotification(User $user): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_WELCOME,
            'Welcome to ShopPro! 🎉',
            "Hi {$user->name}, welcome to ShopPro! Start exploring our amazing products and enjoy a great shopping experience.",
            '/home'
        );
    }

    /**
     * Send low stock alert to seller.
     */
    public function sendLowStockAlert(User $seller, $product): Notification
    {
        return $this->createNotification(
            $seller,
            Notification::TYPE_LOW_STOCK,
            'Low Stock Alert ⚠️',
            "Your product \"{$product->name}\" is running low on stock ({$product->stock_quantity} remaining).",
            "/products/{$product->id}",
            ['product_id' => $product->id, 'stock' => $product->stock_quantity]
        );
    }

    /**
     * Send review request notification.
     */
    public function sendReviewRequest(User $user, Order $order): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_REVIEW_REQUEST,
            'How was your order? ⭐',
            "Your order #{$order->id} was delivered recently. We'd love to hear your feedback!",
            "/orders/{$order->id}",
            ['order_id' => $order->id]
        );
    }

    /**
     * Send abandoned cart reminder.
     */
    public function sendAbandonedCartReminder(User $user): Notification
    {
        return $this->createNotification(
            $user,
            Notification::TYPE_ABANDONED_CART,
            'Your cart misses you! 🛒',
            'You have items waiting in your cart. Complete your purchase before they sell out!',
            '/cart'
        );
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Check if user has opted in for a notification type.
     */
    public function shouldSendEmail(User $user, string $type): bool
    {
        $preferences = $user->email_preferences ?? [];

        // Default to true if no preference set
        return $preferences[$type] ?? true;
    }

    /**
     * Get default email preferences.
     */
    public static function getDefaultPreferences(): array
    {
        return [
            'order_updates'   => true,
            'shipping_updates'=> true,
            'promotions'      => true,
            'price_drops'     => true,
            'review_requests' => true,
            'account_updates' => true,
            'low_stock_alerts'=> true,
            'cart_reminders'  => true,
        ];
    }
}
