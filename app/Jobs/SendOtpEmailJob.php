<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Models\EmailLog;
use App\Models\User;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $email;
    public $otp;

    public function __construct($email, $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }

    public function handle(): void
    {
        $user = User::where('email', $this->email)->first();
        
        $log = EmailLog::create([
            'recipient_email' => $this->email,
            'template_name' => 'OtpMail',
            'status' => 'pending',
            'user_id' => $user ? $user->id : null,
        ]);

        try {
            Mail::to($this->email)->send(new OtpMail($this->otp));
            $log->update(['status' => 'sent']);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
