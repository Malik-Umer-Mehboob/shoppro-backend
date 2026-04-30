<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    protected $campaignService;

    public function __construct(EmailCampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Get campaigns list and global stats.
     */
    public function index()
    {
        $campaigns = EmailCampaign::with(['segment'])
            ->latest()
            ->get()
            ->map(function($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'subject' => $campaign->subject,
                    'status' => $campaign->status,
                    'segment_name' => $campaign->segment?->name ?? ($campaign->results['builtin_segment_name'] ?? 'All Users'),
                    'sent_count' => $campaign->sent_count ?? 0,
                    'open_count' => $campaign->open_count ?? 0,
                    'click_count' => $campaign->click_count ?? 0,
                    'revenue' => (float)($campaign->revenue ?? 0),
                    'scheduled_at' => $campaign->scheduled_at?->format('M d, Y') ?? null,
                    'created_at' => $campaign->created_at->format('M d, Y'),
                ];
            });

        // Calculate real stats from database
        $totalSent = EmailCampaign::sum('sent_count') ?? 0;

        $avgOpenRate = 0;
        $avgClickRate = 0;
        $campaignsWithSent = EmailCampaign::where('sent_count', '>', 0)->get();

        if ($campaignsWithSent->count() > 0) {
            $avgOpenRate = round(
                $campaignsWithSent->avg(function($c) {
                    return $c->sent_count > 0 
                        ? ($c->open_count / $c->sent_count) * 100 
                        : 0;
                }), 1
            );
            $avgClickRate = round(
                $campaignsWithSent->avg(function($c) {
                    return $c->sent_count > 0 
                        ? ($c->click_count / $c->sent_count) * 100 
                        : 0;
                }), 1
            );
        }

        $totalRevenue = (float)(EmailCampaign::sum('revenue') ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'campaigns' => $campaigns,
                'stats' => [
                    'total_sent' => $totalSent,
                    'avg_open_rate' => $avgOpenRate,
                    'avg_click_rate' => $avgClickRate,
                    'total_revenue' => $totalRevenue,
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string',
            'subject'         => 'required|string',
            'ab_test_subject' => 'nullable|string',
            'content'         => 'required|string',
            'segment_id'      => 'nullable|string',
            'scheduled_at'    => 'nullable|date',
            'status'          => 'string|in:draft,scheduled',
        ]);

        if (isset($data['segment_id'])) {
            if (str_starts_with($data['segment_id'], 'custom_')) {
                $data['segment_id'] = (int) str_replace('custom_', '', $data['segment_id']);
            } elseif (!is_numeric($data['segment_id'])) {
                // Built-in segment
                $builtinNames = [
                    'all_users' => 'All Users',
                    'all_customers' => 'All Customers',
                    'all_sellers' => 'All Sellers',
                    'new_users' => 'New Users',
                    'newsletter_subscribers' => 'Subscribers',
                ];
                $data['results'] = [
                    'builtin_segment' => $data['segment_id'],
                    'builtin_segment_name' => $builtinNames[$data['segment_id']] ?? 'All Users'
                ];
                $data['segment_id'] = null;
            }
        }

        $campaign = $this->campaignService->createCampaign($data);

        return response()->json(['success' => true, 'data' => $campaign], 201);
    }

    public function show($id)
    {
        $campaign = EmailCampaign::with(['segment', 'analytics.user'])->findOrFail($id);
        $analytics = $this->campaignService->getCampaignAnalytics($id);

        return response()->json([
            'success'   => true,
            'data'      => $campaign,
            'analytics' => $analytics
        ]);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'name'            => 'string',
            'subject'         => 'string',
            'ab_test_subject' => 'nullable|string',
            'content'         => 'string',
            'segment_id'      => 'nullable|string',
            'scheduled_at'    => 'nullable|date',
            'status'          => 'string|in:draft,scheduled',
        ]);

        if (isset($data['segment_id'])) {
            if (str_starts_with($data['segment_id'], 'custom_')) {
                $data['segment_id'] = (int) str_replace('custom_', '', $data['segment_id']);
            } elseif (!is_numeric($data['segment_id'])) {
                // Built-in segment
                $builtinNames = [
                    'all_users' => 'All Users',
                    'all_customers' => 'All Customers',
                    'all_sellers' => 'All Sellers',
                    'new_users' => 'New Users',
                    'newsletter_subscribers' => 'Subscribers',
                ];
                $data['results'] = [
                    'builtin_segment' => $data['segment_id'],
                    'builtin_segment_name' => $builtinNames[$data['segment_id']] ?? 'All Users'
                ];
                $data['segment_id'] = null;
            }
        }

        $campaign = $this->campaignService->updateCampaign($id, $data);

        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function send($id)
    {
        $campaign = $this->campaignService->sendCampaign($id);
        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function destroy($id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->delete();
        return response()->json(['success' => true, 'message' => 'Campaign deleted.']);
    }
}
