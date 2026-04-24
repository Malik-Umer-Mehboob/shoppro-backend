<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'product_id',
        'category_id',
        'name',
        'type',
        'value',
        'badge_text',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getIsActiveNowAttribute()
    {
        $now = now();
        return $this->is_active && 
               (!$this->starts_at || $this->starts_at <= $now) && 
               (!$this->ends_at || $this->ends_at >= $now);
    }
}
