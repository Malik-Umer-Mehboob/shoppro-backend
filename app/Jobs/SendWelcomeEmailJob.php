<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\EmailLog;
use App\Helpers\EmailHelper;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(): void
    {
        $log = EmailLog::create([
            'recipient_email' => $this->user->email,
            'template_name' => 'welcome_email',
            'status' => 'pending',
            'user_id' => $this->user->id,
        ]);

        try {
            EmailHelper::sendTemplate(
                'welcome_email',
                $this->user->email,
                $this->user->name,
                [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->getRoleNames()->first() ?? 'customer',
                    'status' => $this->user->seller_status ?? 'active',
                ]
            );
            $log->update(['status' => 'sent']);
            Log::info("Email dispatched: WelcomeEmail", [$this->user->id]);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("Email failed", [$e->getMessage()]);
            
            try {
                // Fallback to direct mail if template fails or queue fails
                Mail::raw("Welcome to ShopPro, {$this->user->name}!", function ($message) {
                    $message->to($this->user->email)->subject("Welcome to ShopPro!");
                });
            } catch (\Exception $e2) {
                Log::error("Email fallback failed", [$e2->getMessage()]);
            }
            
            throw $e;
        }
    }
}
