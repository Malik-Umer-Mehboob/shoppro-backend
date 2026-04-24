<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    protected $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'subscribed_to_newsletter' => $user->subscribed_to_newsletter,
                'email_preferences'        => $user->getPreferences(),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'subscribed_to_newsletter' => 'required|boolean',
            'email_preferences'        => 'required|array',
        ]);

        $user = $request->user();
        $user->update([
            'subscribed_to_newsletter' => $request->subscribed_to_newsletter,
            'email_preferences'        => $request->email_preferences,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data'    => $user
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $user = $request->user();
        $this->newsletterService->unsubscribe($user);

        return response()->json([
            'success' => true,
            'message' => 'You have been unsubscribed from our newsletter.'
        ]);
    }
}
