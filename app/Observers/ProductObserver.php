<?php

namespace App\Observers;

use App\Models\Product;
use App\Events\InventoryLow;

class ProductObserver
{
    public function updated(Product $product): void
    {
        // Invalidate homepage cache when featured products or status changes
        if ($product->isDirty(['status', 'moderation_status', 'is_featured', 'price', 'sale_price', 'thumbnail'])) {
            \Illuminate\Support\Facades\Cache::forget('homepage_data');
        }

        // Check if stock quantity has decreased and crossed the threshold
        if ($product->isDirty('stock_quantity')) {
            $oldStock = $product->getOriginal('stock_quantity');
            $newStock = $product->stock_quantity;
            $threshold = 5;

            // Trigger event if stock falls to or below threshold, or reaches zero
            if (($newStock <= $threshold && $oldStock > $threshold) || ($newStock === 0 && $oldStock > 0)) {
                event(new \App\Events\InventoryLow($product));
            }
        }
    }

    public function created(Product $product): void
    {
        \Illuminate\Support\Facades\Cache::forget('homepage_data');
    }

    public function deleted(Product $product): void
    {
        \Illuminate\Support\Facades\Cache::forget('homepage_data');
    }
}
