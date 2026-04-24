<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLowStockAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        // Find published products at or below their low stock threshold
        $lowStockProducts = Product::where('status', 'published')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->with('seller')
            ->get();

        foreach ($lowStockProducts as $product) {
            if ($product->seller && $notificationService->shouldSendEmail($product->seller, 'low_stock_alerts')) {
                $notificationService->sendLowStockAlert($product->seller, $product);
            }
        }
    }
}
