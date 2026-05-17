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

        $query = null;
        if ($campaign->segment) {
            $query = clone $campaign->segment->getMatchingUsersQuery();
        } elseif (isset($campaign->results['builtin_segment'])) {
            $builtin = $campaign->results['builtin_segment'];
            switch ($builtin) {
                case 'all_users':
                    $query = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');});
                    break;
                case 'all_customers':
                    $query = \App\Models\User::whereHas('roles', function($q){$q->where('name', 'customer');});
                    break;
                case 'all_sellers':
                    $query = \App\Models\User::whereHas('roles', function($q){$q->where('name', 'seller');});
                    break;
                case 'new_users':
                    $query = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');})
                        ->where('created_at', '>=', now()->subDays(30));
                    break;
                case 'newsletter_subscribers':
                    $query = \App\Models\User::where('subscribed_to_newsletter', true);
                    break;
                default:
                    $query = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');});
            }
        } else {
            // Default fallback
            $query = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');});
        }

        // Use chunking to optimize performance
        $query->chunk(500, function ($users) use ($campaign) {
            foreach ($users as $user) {
                SendEmailCampaignJob::dispatch($campaign, $user);
            }
        });

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
