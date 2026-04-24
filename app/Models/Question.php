<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'question',
        'answer',
        'answered_by',
        'answered_at',
        'language_id',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function isAnswered(): bool
    {
        return $this->answer !== null;
    }
}
