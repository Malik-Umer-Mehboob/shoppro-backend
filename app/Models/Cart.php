<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_code',
        'status',
        'total_items',
        'total_quantity',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Update the cart totals based on items.
     */
    public function updateTotals(): void
    {
        $this->load(['items.product', 'items.variant']);
        
        $subtotal = 0;
        $totalQuantity = 0;
        $totalItems = $this->items->count();

        foreach ($this->items as $item) {
            $subtotal += $item->price * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        // Simple placeholders for tax and shipping
        // You can implement more complex logic here
        $taxRate = 0.10; // 10% tax
        $flatShipping = $totalQuantity > 0 ? 10.00 : 0.00;

        $this->subtotal = $subtotal;
        $this->total_items = $totalItems;
        $this->total_quantity = $totalQuantity;
        $this->tax_amount = round($subtotal * $taxRate, 2);
        $this->shipping_amount = $flatShipping;
        
        // Final total calculation
        $this->total = ($this->subtotal + $this->tax_amount + $this->shipping_amount) - $this->discount_amount;
        
        if ($this->total < 0) $this->total = 0;

        $this->save();
    }
}
