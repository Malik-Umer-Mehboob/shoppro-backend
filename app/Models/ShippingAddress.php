<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set this address as default and un-default all others for the user.
     */
    public function setAsDefault(): void
    {
        // Un-default all addresses for this user
        static::where('user_id', $this->user_id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Return address as a plain array for storing in orders.
     */
    public function toOrderArray(): array
    {
        return [
            'full_name'      => $this->full_name,
            'phone'          => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city'           => $this->city,
            'state'          => $this->state,
            'country'        => $this->country,
            'postal_code'    => $this->postal_code,
        ];
    }
}
