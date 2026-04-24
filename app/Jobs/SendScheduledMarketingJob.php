<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailCampaignService;
use App\Services\NewsletterService;

class SendScheduledMarketingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(EmailCampaignService $campaignService, NewsletterService $newsletterService)
    {
        // Scheduled Campaigns
        $campaigns = EmailCampaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaignService->sendCampaign($campaign->id);
        }

        // Scheduled Newsletters
        $newsletters = Newsletter::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($newsletters as $newsletter) {
            $newsletterService->sendNewsletterToSubscribers($newsletter->id);
        }
    }
}
