<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'locale',
        'direction',
        'is_active',
        'currency_code',
        'currency_symbol',
        'exchange_rate',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'exchange_rate' => 'decimal:6',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }
}
