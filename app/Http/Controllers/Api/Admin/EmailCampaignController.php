<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailCampaignService;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    protected $campaignService;

    public function __construct(EmailCampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => \App\Models\EmailCampaign::with('segment')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string',
            'subject'         => 'required|string',
            'ab_test_subject' => 'nullable|string',
            'content'         => 'required|string',
            'segment_id'      => 'nullable|exists:user_segments,id',
            'scheduled_at'    => 'nullable|date',
            'status'          => 'string|in:draft,scheduled',
        ]);

        $campaign = $this->campaignService->createCampaign($data);

        return response()->json(['success' => true, 'data' => $campaign], 201);
    }

    public function show($id)
    {
        $campaign = \App\Models\EmailCampaign::with(['segment', 'analytics.user'])->findOrFail($id);
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
            'segment_id'      => 'nullable|exists:user_segments,id',
            'scheduled_at'    => 'nullable|date',
            'status'          => 'string|in:draft,scheduled',
        ]);

        $campaign = $this->campaignService->updateCampaign($id, $data);

        return response()->json(['success' => true, 'data' => $campaign]);
    }

    public function send($id)
    {
        $campaign = $this->campaignService->sendCampaign($id);
        return response()->json(['success' => true, 'data' => $campaign]);
    }
}
