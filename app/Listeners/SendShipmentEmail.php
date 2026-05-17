<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShippedMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;

class SendShipmentEmail implements ShouldQueue
{
    public $tries = 3;
    public function handle(OrderShipped $event): void
    {
        $order = $event->order->load(['customer', 'items.product']);

        $log = \App\Models\EmailLog::create([
            'recipient_email' => $order->customer->email,
            'template_name' => 'OrderShippedMail',
            'status' => 'pending',
            'user_id' => $order->customer->id
        ]);

        try {
            Mail::to($order->customer->email)->send(new OrderShippedMail($order));
            $log->update(['status' => 'sent']);

            $notificationService = app(NotificationService::class);
            $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_SHIPPED);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
