<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',
        'billed_to',
        'shipped_to',
        'sub_total',
        'shipping_cost',
        'tax',
        'discount',
        'total',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'total'   => 'float',
        'sub_total' => 'float',
        'shipping_cost' => 'float',
        'tax' => 'float',
        'discount' => 'float',
        'billed_to' => 'array',
        'shipped_to' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . strtoupper(Str::random(4)) . '-' . date('Ymd') . '-' . $invoice->order_id;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class, 'order_id', 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
