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
                'Payment Confirmed! 💳',
                "Your payment for order #{$order->order_number} has been verified. We are now processing your shipment.",
                'payment.verified',
                NotificationService::PRIORITY_HIGH,
                ['order_id' => $order->id],
                '/user/orders'
            );
        }
    }
}
