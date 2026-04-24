<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class NotifySeller
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('seller');

        if ($order->seller) {
            Log::info("Seller notified for new order #{$order->id}: {$order->seller->email}");

            // Create in-app notification for seller
            $notificationService = app(NotificationService::class);
            $notificationService->notifySellerNewOrder($order);
        }
    }
}
