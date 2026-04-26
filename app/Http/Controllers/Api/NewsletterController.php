<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    // Public unsubscribe via token (no auth needed)
    public function unsubscribeByToken(Request $request, $token)
    {
        $user = User::where('unsubscribe_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe link',
            ], 404);
        }

        $user->update(['subscribed_to_newsletter' => false]);

        // Return HTML page for browser
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'You have been unsubscribed successfully',
            ]);
        }

        return response(
            view()->exists('unsubscribe') 
            ? view('unsubscribe', ['name' => $user->name])
            : "<html><body style='font-family:sans-serif;text-align:center;padding:50px;background:#0F172A;color:white;'><h1 style='color:#F97316;'>Unsubscribed</h1><p>You have been successfully unsubscribed from ShopPro newsletters.</p><p><a href='" . env('FRONTEND_URL') . "' style='color:#F97316;'>Back to ShopPro</a></p></body></html>"
        );
    }

    // Resubscribe by token
    public function resubscribeByToken($token)
    {
        $user = User::where('unsubscribe_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid link',
            ], 404);
        }

        $user->update(['subscribed_to_newsletter' => true]);

        return response()->json([
            'success' => true,
            'message' => 'You have been resubscribed!',
        ]);
    }

    // Auth-required subscribe
    public function subscribe(Request $request)
    {
        $request->user()->update(['subscribed_to_newsletter' => true]);
        return response()->json([
            'success' => true,
            'message' => 'Subscribed to newsletter',
        ]);
    }

    // Auth-required unsubscribe
    public function unsubscribe(Request $request)
    {
        $request->user()->update(['subscribed_to_newsletter' => false]);
        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed from newsletter',
        ]);
    }

    // Helper to get unsubscribe URL for emails
    public static function getUnsubscribeUrl($user): string
    {
        if (!$user->unsubscribe_token) {
            $user->update([
                'unsubscribe_token' => Str::random(64)
            ]);
            $user->refresh();
        }
        return env('APP_URL') . '/api/newsletter/unsubscribe/' . $user->unsubscribe_token;
    }
}
