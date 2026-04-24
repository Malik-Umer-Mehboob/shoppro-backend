<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignAnalytics;
use App\Jobs\SendEmailCampaignJob;
use Carbon\Carbon;

class EmailCampaignService
{
    public function createCampaign(array $data)
    {
        return EmailCampaign::create($data);
    }

    public function updateCampaign($campaignId, array $data)
    {
        $campaign = EmailCampaign::findOrFail($campaignId);
        $campaign->update($data);
        return $campaign;
    }

    public function deleteCampaign($campaignId)
    {
        $campaign = EmailCampaign::findOrFail($campaignId);
        return $campaign->delete();
    }

    public function scheduleCampaign($campaignId, $scheduledAt)
    {
        $campaign = EmailCampaign::findOrFail($campaignId);
        $campaign->update([
            'scheduled_at' => Carbon::parse($scheduledAt),
            'status'       => 'scheduled'
        ]);
        return $campaign;
    }

    public function sendCampaign($campaignId)
    {
        $campaign = EmailCampaign::findOrFail($campaignId);
        $campaign->update(['status' => 'sending']);

        $users = $campaign->segment->getMatchingUsers();

        foreach ($users as $user) {
            SendEmailCampaignJob::dispatch($campaign, $user);
        }

        $campaign->update(['status' => 'sent']);
        return $campaign;
    }

    public function getCampaignAnalytics($campaignId)
    {
        $campaign = EmailCampaign::findOrFail($campaignId);
        
        return [
            'total_sent'   => $campaign->analytics()->count(),
            'total_opens'  => $campaign->analytics()->whereNotNull('opened_at')->count(),
            'total_clicks' => $campaign->analytics()->whereNotNull('clicked_at')->count(),
            'conversions'  => $campaign->analytics()->whereNotNull('converted_at')->count(),
        ];
    }
}
