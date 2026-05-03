<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'subcategory_name',
        'parent_id',
        'description',
        'reason',
        'status',
        'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
