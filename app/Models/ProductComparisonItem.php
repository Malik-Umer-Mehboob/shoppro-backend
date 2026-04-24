<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComparisonItem extends Model
{
    use HasFactory;

    protected $fillable = ['comparison_id', 'product_id'];

    public function comparison()
    {
        return $this->belongsTo(ProductComparison::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
