<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAbandonedCartReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        // Find carts updated more than 24 hours ago with items
        $abandonedCarts = Cart::where('updated_at', '<=', now()->subHours(24))
            ->whereHas('items')
            ->with('user')
            ->get();

        foreach ($abandonedCarts as $cart) {
            if ($cart->user && $notificationService->shouldSendEmail($cart->user, 'cart_reminders')) {
                $notificationService->sendAbandonedCartReminder($cart->user);
            }
        }
    }
}
