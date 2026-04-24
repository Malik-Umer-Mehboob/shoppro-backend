<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'material',
        'sku',
        'price',
        'stock_quantity',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            if (empty($variant->sku)) {
                $product = $variant->product;
                if ($product) {
                    $prefix = strtoupper(substr($product->name, 0, 3));
                    $parts = [$prefix];
                    if ($variant->size) $parts[] = strtoupper($variant->size);
                    if ($variant->color) $parts[] = strtoupper(substr($variant->color, 0, 3));
                    if ($variant->material) $parts[] = strtoupper(substr($variant->material, 0, 3));
                    
                    $baseSku = implode('-', $parts);
                    $variant->sku = $baseSku . '-' . strtoupper(\Illuminate\Support\Str::random(4));
                }
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
