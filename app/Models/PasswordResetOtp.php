<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'otp',
        'expires_at'
    ];

    protected $dates = [
        'expires_at'
    ];
}
