<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'initial_amount', 'balance', 'buyer_id', 
        'recipient_email', 'message', 'image_url', 'expires_at', 'is_active'
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function isValid()
    {
        return $this->is_active && $this->balance > 0 && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
