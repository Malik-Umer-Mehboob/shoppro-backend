<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComparison extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'session_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ProductComparisonItem::class, 'comparison_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product, 'product_comparison_items', 'comparison_id', 'product_id');
    }
}
