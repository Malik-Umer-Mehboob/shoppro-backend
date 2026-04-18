<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('ShopPro - Password Reset Code')
                    ->html("Your ShopPro password reset OTP is: {$this->otp}<br>This code is valid for 10 minutes.<br>If you did not request this, please ignore this email.");
    }
}
