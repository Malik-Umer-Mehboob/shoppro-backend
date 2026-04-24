<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load(['customer', 'items.product', 'invoice']);

        // Send email
        Mail::to($order->customer->email)->send(new OrderConfirmationMail($order));

        // Create in-app notification
        $notificationService = app(NotificationService::class);
        $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_PLACED);
    }
}
