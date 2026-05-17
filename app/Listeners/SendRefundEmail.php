<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Mail\OrderRefundedMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;

class SendRefundEmail implements ShouldQueue
{
    public $tries = 3;
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order->load(['customer', 'items.product']);

        $log = \App\Models\EmailLog::create([
            'recipient_email' => $order->customer->email,
            'template_name' => 'OrderRefundedMail',
            'status' => 'pending',
            'user_id' => $order->customer->id
        ]);

        try {
            Mail::to($order->customer->email)->send(new OrderRefundedMail($order));
            $log->update(['status' => 'sent']);

            $notificationService = app(NotificationService::class);
            $notificationService->sendOrderNotification($order, Notification::TYPE_ORDER_REFUNDED);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
