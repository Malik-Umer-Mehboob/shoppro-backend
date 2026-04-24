<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    // Status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_RETURNED   = 'returned';
    const STATUS_REFUNDED   = 'refunded';

    // Payment status constants
    const PAYMENT_PENDING  = 'pending';
    const PAYMENT_PAID     = 'paid';
    const PAYMENT_FAILED   = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'seller_id',
        'status',
        'total_items',
        'total_quantity',
        'subtotal',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_id',
        'payment_status',
        'shipping_method',
        'shipping_cost',
        'tax',
        'coupon_code',
        'discount',
        'grand_total',
        'notes',
        'tracking_number',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address'  => 'array',
        'subtotal'         => 'float',
        'shipping_cost'    => 'float',
        'tax'              => 'float',
        'discount'         => 'float',
        'grand_total'      => 'float',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function affiliateOrder(): HasOne
    {
        return $this->hasOne(AffiliateOrder::class);
    }

    public function getTotalRefundedAttribute()
    {
        // Assuming refund_reason implies a refund, and currently we don't have a refunds table.
        // If there's a refund table or total_refund column later, we can update this.
        // For now, if status is 'refunded', we could return grand_total.
        return $this->status === 'refunded' ? $this->grand_total : 0;
    }

    // Helpers

    public function canBeRefunded(): bool
    {
        if ($this->status !== self::STATUS_DELIVERED) {
            return false;
        }
        // Allow refund within 30 days of delivery
        return $this->updated_at->diffInDays(now()) <= 30;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
