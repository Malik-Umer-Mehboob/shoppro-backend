<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'link',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    // Notification types
    const TYPE_ORDER_PLACED    = 'order_placed';
    const TYPE_ORDER_SHIPPED   = 'order_shipped';
    const TYPE_ORDER_DELIVERED = 'order_delivered';
    const TYPE_ORDER_CANCELLED = 'order_cancelled';
    const TYPE_ORDER_REFUNDED  = 'order_refunded';
    const TYPE_WELCOME         = 'welcome';
    const TYPE_PRICE_DROP      = 'price_drop';
    const TYPE_BACK_IN_STOCK   = 'back_in_stock';
    const TYPE_PROMOTION       = 'promotion';
    const TYPE_REVIEW_REQUEST  = 'review_request';
    const TYPE_LOW_STOCK       = 'low_stock';
    const TYPE_ABANDONED_CART  = 'abandoned_cart';
    const TYPE_NEW_ORDER       = 'new_order_seller';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
