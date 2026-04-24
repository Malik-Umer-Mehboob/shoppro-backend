<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Mail\OrderRefundedMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class SendRefundEmail
{
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order->load(['customer', 'items.product']);

        Mail::to($order->customer->email)->send(new OrderRefundedMail($order));

        $notificationService = app(NotificationService::class);
        $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_REFUNDED);
    }
}
