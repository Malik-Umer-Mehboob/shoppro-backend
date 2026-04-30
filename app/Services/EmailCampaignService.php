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

        $users = collect();
        if ($campaign->segment) {
            $users = $campaign->segment->getMatchingUsers();
        } elseif (isset($campaign->results['builtin_segment'])) {
            $builtin = $campaign->results['builtin_segment'];
            switch ($builtin) {
                case 'all_users':
                    $users = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');})->get();
                    break;
                case 'all_customers':
                    $users = \App\Models\User::whereHas('roles', function($q){$q->where('name', 'customer');})->get();
                    break;
                case 'all_sellers':
                    $users = \App\Models\User::whereHas('roles', function($q){$q->where('name', 'seller');})->get();
                    break;
                case 'new_users':
                    $users = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');})
                        ->where('created_at', '>=', now()->subDays(30))->get();
                    break;
                case 'newsletter_subscribers':
                    $users = \App\Models\User::where('subscribed_to_newsletter', true)->get();
                    break;
                default:
                    $users = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');})->get();
            }
        } else {
            // Default fallback
            $users = \App\Models\User::whereDoesntHave('roles', function($q){$q->where('name', 'admin');})->get();
        }

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
