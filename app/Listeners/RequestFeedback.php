<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\OrderDeliveredMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class RequestFeedback
{
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order->load(['customer', 'items.product']);

        Mail::to($order->customer->email)->send(new OrderDeliveredMail($order));

        $notificationService = app(NotificationService::class);
        $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_DELIVERED);
    }
}
