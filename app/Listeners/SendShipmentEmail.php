<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShippedMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class SendShipmentEmail
{
    public function handle(OrderShipped $event): void
    {
        $order = $event->order->load(['customer', 'items.product']);

        Mail::to($order->customer->email)->send(new OrderShippedMail($order));

        $notificationService = app(NotificationService::class);
        $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_SHIPPED);
    }
}
