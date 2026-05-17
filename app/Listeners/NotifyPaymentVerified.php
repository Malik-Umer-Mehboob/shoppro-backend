<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPaymentVerified implements ShouldQueue
{
    public function handle(PaymentVerified $event): void
    {
        $order = $event->order;
        $customer = $order->user;

        if ($customer) {
            NotificationService::send(
                $customer->id,
                'payment.verified',
                'Payment Confirmed! 💳',
                "Your payment for order #{$order->order_number} has been verified. We are now processing your shipment.",
                ['order_id' => $order->id],
                NotificationService::PRIORITY_HIGH,
                '/user/orders'
            );

            \App\Helpers\EmailHelper::sendTemplate(
                'payment_status_email',
                $customer->email,
                $customer->name,
                [
                    'name' => $customer->name,
                    'order_id' => $order->id,
                    'total' => $order->total_amount ?? $order->total,
                ]
            );
        }
    }
}
