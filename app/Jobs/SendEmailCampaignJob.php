<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\User;
use App\Mail\MarketingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    protected $campaign;
    protected $user;

    public function __construct(EmailCampaign $campaign, User $user)
    {
        $this->campaign = $campaign;
        $this->user = $user;
    }

    public function handle()
    {
        $log = \App\Models\EmailLog::create([
            'recipient_email' => $this->user->email,
            'template_name' => 'Campaign: ' . $this->campaign->subject,
            'status' => 'pending',
            'campaign_id' => $this->campaign->id,
            'user_id' => $this->user->id
        ]);

        try {
            // Inject tracking links into content
            $content = $this->campaign->content;
            
            // Example: Replace {{ first_name }}
            $content = str_replace(['{{ first_name }}', '{{ name }}'], $this->user->name, $content);

            // Send email
            Mail::to($this->user->email)->send(new MarketingMail(
                $this->campaign->subject,
                $content,
                $this->campaign->id,
                $this->user->id
            ));

            // Create initial analytics record
            \App\Models\EmailCampaignAnalytics::firstOrCreate([
                'email_campaign_id' => $this->campaign->id,
                'user_id'           => $this->user->id,
            ]);

            $log->update(['status' => 'sent']);
            Log::info("Email dispatched: CampaignEmail", [$this->campaign->id, $this->user->id]);
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error("Email failed", [$e->getMessage()]);
            
            try {
                Mail::raw("Campaign: {$this->campaign->subject}\n\n" . strip_tags($this->campaign->content), function ($message) {
                    $message->to($this->user->email)->subject($this->campaign->subject);
                });
            } catch (\Exception $e2) {
                Log::error("Email fallback failed", [$e2->getMessage()]);
            }
            
            throw $e;
        }
    }
}
