<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    // Get all newsletters
    public function index()
    {
        $newsletters = DB::table('newsletters')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'subject' => $n->subject,
                    'status' => $n->status,
                    'sent_count' => $n->sent_count ?? 0,
                    'open_count' => $n->open_count ?? 0,
                    'open_rate' => ($n->sent_count ?? 0) > 0
                        ? round(($n->open_count / $n->sent_count) * 100, 1)
                        : 0,
                    'created_at' => $n->created_at,
                    'scheduled_at' => $n->scheduled_at ?? null,
                ];
            });

        // Get subscriber count
        $subscriberCount = User::where(
            'subscribed_to_newsletter', true
        )->count();

        return response()->json([
            'success' => true,
            'data' => [
                'newsletters' => $newsletters,
                'subscriber_count' => $subscriberCount,
            ]
        ]);
    }

    // Create newsletter
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $id = DB::table('newsletters')->insertGetId([
            'subject' => $request->subject,
            'content' => $request->content,
            'status' => 'draft',
            'sent_count' => 0,
            'open_count' => 0,
            'scheduled_at' => $request->scheduled_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter created',
            'data' => ['id' => $id],
        ]);
    }

    // Delete newsletter
    public function destroy($id)
    {
        $newsletter = DB::table('newsletters')
            ->where('id', $id)->first();

        if (!$newsletter) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter not found',
            ], 404);
        }

        DB::table('newsletters')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Newsletter deleted successfully',
        ]);
    }

    // Send newsletter to all subscribers
    public function send($id)
    {
        $newsletter = DB::table('newsletters')
            ->where('id', $id)->first();

        if (!$newsletter) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter not found',
            ], 404);
        }

        if ($newsletter->status === 'sent'
            && $newsletter->sent_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter already sent',
            ], 422);
        }

        $subscribers = User::where(
            'subscribed_to_newsletter', true
        )->get();

        if ($subscribers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No subscribers found. ' .
                    'Users must subscribe to receive newsletters.',
            ], 422);
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::send([], [], function ($message)
                    use ($subscriber, $newsletter) {
                    $message
                        ->to($subscriber->email, $subscriber->name)
                        ->subject($newsletter->subject)
                        ->html(
                            nl2br(e($newsletter->content))
                            . '<br><br><hr>'
                            . '<p style="font-size:12px;color:#666;">'
                            . 'To unsubscribe, click here: '
                            . '<a href="' . env('APP_URL')
                            . '/api/newsletter/unsubscribe/'
                            . $subscriber->unsubscribe_token . '">'
                            . 'Unsubscribe</a></p>'
                        );
                });
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Newsletter email failed: '
                    . $e->getMessage());
                continue;
            }
        }

        DB::table('newsletters')->where('id', $id)->update([
            'status' => 'sent',
            'sent_count' => $sentCount,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Newsletter sent to {$sentCount} subscribers!"
                . ($failedCount > 0 ? " ({$failedCount} failed)" : ''),
            'data' => [
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]
        ]);
    }

    // Get newsletter report/stats
    public function report($id)
    {
        $newsletter = DB::table('newsletters')
            ->where('id', $id)->first();

        if (!$newsletter) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter not found',
            ], 404);
        }

        $subscriberCount = User::where(
            'subscribed_to_newsletter', true
        )->count();

        $openRate = ($newsletter->sent_count ?? 0) > 0
            ? round(($newsletter->open_count
                / $newsletter->sent_count) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $newsletter->id,
                'subject' => $newsletter->subject,
                'content' => $newsletter->content,
                'status' => $newsletter->status,
                'sent_count' => $newsletter->sent_count ?? 0,
                'open_count' => $newsletter->open_count ?? 0,
                'open_rate' => $openRate,
                'subscriber_count' => $subscriberCount,
                'sent_at' => $newsletter->updated_at,
                'created_at' => $newsletter->created_at,
            ]
        ]);
    }

    // Get subscribers list
    public function subscribers()
    {
        $subscribers = User::where('subscribed_to_newsletter', true)
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'subscribed_at' => $u->created_at
                        ->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subscribers,
        ]);
    }
}
