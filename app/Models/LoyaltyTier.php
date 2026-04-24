<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'threshold', 'benefits'];

    protected $casts = [
        'benefits' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
