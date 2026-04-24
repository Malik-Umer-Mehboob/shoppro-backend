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

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    protected $user;

    public function __construct(EmailCampaign $campaign, User $user)
    {
        $this->campaign = $campaign;
        $this->user = $user;
    }

    public function handle()
    {
        // Inject tracking links into content
        $content = $this->campaign->content;
        
        // Example: Replace {{ first_name }}
        $content = str_replace('{{ first_name }}', $this->user->name, $content);

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
    }
}
