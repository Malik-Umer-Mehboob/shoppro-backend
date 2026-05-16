<?php

namespace App\Observers;

use App\Models\Product;
use App\Events\InventoryLow;

class ProductObserver
{
    public function updated(Product $product): void
    {
        // Check if stock quantity has decreased and crossed the threshold
        if ($product->isDirty('stock_quantity')) {
            $oldStock = $product->getOriginal('stock_quantity');
            $newStock = $product->stock_quantity;
            $threshold = 5;

            // Trigger event if stock falls to or below threshold, or reaches zero
            if (($newStock <= $threshold && $oldStock > $threshold) || ($newStock === 0 && $oldStock > 0)) {
                event(new InventoryLow($product));
            }
        }
    }
}
