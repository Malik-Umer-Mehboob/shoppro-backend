<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaignAnalytics;
use Illuminate\Http\Request;

class EmailAnalyticsController extends Controller
{
    /**
     * Track email open via a 1x1 transparent pixel.
     */
    public function trackOpen(Request $request, $campaignId, $userId)
    {
        EmailCampaignAnalytics::updateOrCreate(
            ['email_campaign_id' => $campaignId, 'user_id' => $userId],
            ['opened_at' => now()]
        );

        // Return 1x1 transparent PNG
        return response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='))
            ->header('Content-Type', 'image/png');
    }

    /**
     * Track link click and redirect to the target URL.
     */
    public function trackClick(Request $request, $campaignId, $userId)
    {
        $targetUrl = $request->query('url');

        EmailCampaignAnalytics::updateOrCreate(
            ['email_campaign_id' => $campaignId, 'user_id' => $userId],
            ['clicked_at' => now()]
        );

        return redirect($targetUrl);
    }
}
