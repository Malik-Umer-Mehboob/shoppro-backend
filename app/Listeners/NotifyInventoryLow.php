<?php

namespace App\Listeners;

use App\Events\InventoryLow;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyInventoryLow implements ShouldQueue
{
    public function handle(InventoryLow $event): void
    {
        $product = $event->product;
        $seller = $product->seller;

        $title = $product->stock_quantity === 0 ? 'Out of Stock! 🚫' : 'Low Stock Alert! ⚠️';
        $message = "'{$product->name}' has {$product->stock_quantity} units remaining.";

        // Notify Admins
        NotificationService::notifyAdmins(
            $title,
            $message,
            'stock.low',
            NotificationService::PRIORITY_CRITICAL,
            ['product_id' => $product->id],
            '/admin/low-stock'
        );

        // Notify Seller
        if ($seller) {
            NotificationService::send(
                $seller->id,
                $title,
                $message,
                'stock.low',
                NotificationService::PRIORITY_CRITICAL,
                ['product_id' => $product->id],
                '/seller/products'
            );
        }
    }
}
