<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EmailCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::latest()->get()
            ->map(function ($campaign) {
                // Get segment name from results field
                $segmentName = 'No Segment';
                if ($campaign->results) {
                    try {
                        $results = is_array($campaign->results)
                            ? $campaign->results
                            : json_decode($campaign->results, true);
                        $segmentName = $results['builtin_segment_name']
                            ?? 'Custom';
                    } catch (\Exception $e) {}
                }

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'subject' => $campaign->subject,
                    'status' => $campaign->status,
                    'segment_name' => $segmentName,
                    'sent_count' => $campaign->sent_count,
                    'open_count' => $campaign->open_count,
                    'click_count' => $campaign->click_count,
                    'open_rate' => $campaign->sent_count > 0
                        ? round(($campaign->open_count /
                            $campaign->sent_count) * 100, 1)
                        : 0,
                    'click_rate' => $campaign->sent_count > 0
                        ? round(($campaign->click_count /
                            $campaign->sent_count) * 100, 1)
                        : 0,
                    'created_at' => $campaign->created_at->format('M d, Y'),
                ];
            });

        // Stats
        $totalSent = EmailCampaign::sum('sent_count');
        $campaignsWithSent = EmailCampaign::where(
            'sent_count', '>', 0
        )->get();

        $avgOpenRate = 0;
        $avgClickRate = 0;

        if ($campaignsWithSent->count() > 0) {
            $avgOpenRate = round($campaignsWithSent->avg(function ($c) {
                return $c->sent_count > 0
                    ? ($c->open_count / $c->sent_count) * 100
                    : 0;
            }), 1);
            $avgClickRate = round($campaignsWithSent->avg(function ($c) {
                return $c->sent_count > 0
                    ? ($c->click_count / $c->sent_count) * 100
                    : 0;
            }), 1);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'campaigns' => $campaigns,
                'stats' => [
                    'total_sent' => $totalSent,
                    'avg_open_rate' => $avgOpenRate,
                    'avg_click_rate' => $avgClickRate,
                    'total_revenue' => EmailCampaign::sum('revenue'),
                ],
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'ab_test_subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'segment_id' => 'nullable|string',
        ]);

        // Store builtin segment in results field
        $results = null;
        if (!empty($validated['segment_id'])) {
            $results = [
                'builtin_segment' => $validated['segment_id'],
                'builtin_segment_name' => $this->getSegmentName(
                    $validated['segment_id']
                ),
            ];
        }

        $campaign = EmailCampaign::create([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'ab_test_subject' => $validated['ab_test_subject'] ?? null,
            'content' => $validated['content'] ?? null,
            'segment_id' => null, // Keep null, use results for builtin
            'status' => 'draft',
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
            'revenue' => 0,
            'results' => $results,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Campaign created successfully',
            'data' => $campaign,
        ]);
    }

    public function show($id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    public function update(Request $request, $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:255',
            'subject' => 'string|max:255',
            'ab_test_subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'segment_id' => 'nullable|string',
        ]);

        if (isset($validated['segment_id'])) {
            $validated['results'] = [
                'builtin_segment' => $validated['segment_id'],
                'builtin_segment_name' => $this->getSegmentName(
                    $validated['segment_id']
                ),
            ];
            $validated['segment_id'] = null;
        }

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campaign updated successfully',
            'data' => $campaign,
        ]);
    }

    public function destroy($id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }

    public function send($id)
    {
        $campaign = EmailCampaign::findOrFail($id);

        if ($campaign->status === 'sent' && $campaign->sent_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign already sent',
            ], 422);
        }

        // Determine segment ID from multiple possible sources
        $segmentId = $campaign->segment_id;

        // Check results field for builtin segment
        if (!$segmentId && $campaign->results) {
            try {
                $results = is_array($campaign->results)
                    ? $campaign->results
                    : json_decode($campaign->results, true);

                if (!empty($results['builtin_segment'])) {
                    $segmentId = $results['builtin_segment'];
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        // If still no segment, default to all_users
        if (!$segmentId) {
            $segmentId = 'all_users';
        }

        // Get users based on segment
        $users = $this->getUsersBySegment($segmentId);

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in segment: ' . $segmentId,
            ], 422);
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                Mail::send([], [], function ($message)
                    use ($user, $campaign) {
                    $message
                        ->to($user->email, $user->name)
                        ->subject($campaign->subject)
                        ->html(
                            $campaign->content
                                ? nl2br(e($campaign->content))
                                : '<p>' . e($campaign->subject) . '</p>'
                        );
                });
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Campaign email failed for ' .
                    $user->email . ': ' . $e->getMessage());
                continue;
            }
        }

        $campaign->update([
            'status' => 'sent',
            'sent_count' => $sentCount,
        ]);

        $message = "Campaign sent to {$sentCount} users!";
        if ($failedCount > 0) {
            $message .= " ({$failedCount} failed)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'segment' => $segmentId,
            ]
        ]);
    }

    public function getSegments()
    {
        // Built-in default segments (always available)
        $defaultSegments = [
            [
                'id' => 'all_users',
                'name' => 'All Users',
                'description' => 'All registered users',
                'count' => User::whereDoesntHave('roles',
                    function ($q) {
                        $q->where('name', 'admin');
                    })->count(),
            ],
            [
                'id' => 'all_customers',
                'name' => 'All Customers',
                'description' => 'Users with customer role',
                'count' => User::whereHas('roles',
                    function ($q) {
                        $q->where('name', 'customer');
                    })->count(),
            ],
            [
                'id' => 'all_sellers',
                'name' => 'All Sellers',
                'description' => 'Users with seller role',
                'count' => User::whereHas('roles',
                    function ($q) {
                        $q->where('name', 'seller');
                    })->count(),
            ],
            [
                'id' => 'new_users',
                'name' => 'New Users (Last 30 days)',
                'description' => 'Users who registered in last 30 days',
                'count' => User::whereDoesntHave('roles',
                    function ($q) {
                        $q->where('name', 'admin');
                    })
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
            ],
            [
                'id' => 'newsletter_subscribers',
                'name' => 'Newsletter Subscribers',
                'description' => 'Users subscribed to newsletter',
                'count' => User::where(
                    'subscribed_to_newsletter', true
                )->count(),
            ],
        ];

        // Also fetch custom segments from user_segments table if exists
        $customSegments = [];
        try {
            $customSegments = DB::table('user_segments')
                ->select('id', 'name')
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => 'custom_' . $s->id,
                        'name' => $s->name . ' (Custom)',
                        'description' => 'Custom segment',
                        'count' => 0,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Table might not exist, ignore
        }

        $allSegments = array_merge($defaultSegments, $customSegments);

        return response()->json([
            'success' => true,
            'data' => $allSegments,
        ]);
    }

    private function getUsersBySegment(
        $segmentId
    ): \Illuminate\Support\Collection {
        return match($segmentId) {
            'all_users' => User::whereDoesntHave('roles',
                fn($q) => $q->where('name', 'admin')
            )->get(),

            'all_customers' => User::whereHas('roles',
                fn($q) => $q->where('name', 'customer')
            )->get(),

            'all_sellers' => User::whereHas('roles',
                fn($q) => $q->where('name', 'seller')
            )->get(),

            'all_support' => User::whereHas('roles',
                fn($q) => $q->where('name', 'support')
            )->get(),

            'all_riders' => User::whereHas('roles',
                fn($q) => $q->where('name', 'rider')
            )->get(),

            'new_users' => User::whereDoesntHave('roles',
                fn($q) => $q->where('name', 'admin')
            )->where('created_at', '>=', now()->subDays(30))->get(),

            'newsletter_subscribers' => User::where(
                'subscribed_to_newsletter', true
            )->get(),

            default => collect([]),
        };
    }

    private function getSegmentName(string $segmentId): string
    {
        return match($segmentId) {
            'all_users' => 'All Users',
            'all_customers' => 'All Customers',
            'all_sellers' => 'All Sellers',
            'new_users' => 'New Users (Last 30 days)',
            'newsletter_subscribers' => 'Newsletter Subscribers',
            default => 'Custom Segment',
        };
    }
}
