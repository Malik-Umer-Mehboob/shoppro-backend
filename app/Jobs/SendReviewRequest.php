<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReviewRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        // Find orders delivered 7 days ago
        $orders = Order::where('status', Order::STATUS_DELIVERED)
            ->where('updated_at', '<=', now()->subDays(7))
            ->where('updated_at', '>=', now()->subDays(8))
            ->with('customer')
            ->get();

        foreach ($orders as $order) {
            if ($order->customer && $notificationService->shouldSendEmail($order->customer, 'review_requests')) {
                $notificationService->sendReviewRequest($order->customer, $order);
            }
        }
    }
}
