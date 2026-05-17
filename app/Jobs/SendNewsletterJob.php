<?php

namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\User;
use App\Models\User;
use App\Mail\NewsletterMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    protected $campaign;
    protected $subscriber;

    public function __construct($campaign, User $subscriber)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
    }

    public function handle()
    {
        $log = \App\Models\EmailLog::create([
            'recipient_email' => $this->subscriber->email,
            'template_name' => 'Newsletter: ' . $this->campaign->subject,
            'status' => 'pending',
            'user_id' => $this->subscriber->id
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($this->subscriber->email)->send(new NewsletterMail($this->campaign));

            $log->update(['status' => 'sent']);
            Log::info("Newsletter sent to: " . $this->subscriber->email);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("Email failed", [$e->getMessage()]);
            
            try {
                Mail::raw("Newsletter: {$this->campaign->subject}\n\n" . strip_tags($this->campaign->content), function ($message) {
                    $message->to($this->subscriber->email)->subject($this->campaign->subject);
                });
            } catch (\Exception $e2) {
                Log::error("Email fallback failed", [$e2->getMessage()]);
            }
            
            throw $e;
        }
    }
}
