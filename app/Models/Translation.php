<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'key',
        'text',
        'language_id',
        'group',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
