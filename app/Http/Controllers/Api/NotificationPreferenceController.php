<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * GET /api/user/notification-preferences
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $preferences = $user->email_preferences ?? NotificationService::getDefaultPreferences();

        return response()->json([
            'email_preferences' => $preferences,
            'mobile_number'     => $user->mobile_number,
        ]);
    }

    /**
     * PUT /api/user/notification-preferences
     */
    public function update(Request $request)
    {
        $request->validate([
            'email_preferences'                  => 'sometimes|array',
            'email_preferences.order_updates'    => 'sometimes|boolean',
            'email_preferences.shipping_updates' => 'sometimes|boolean',
            'email_preferences.promotions'       => 'sometimes|boolean',
            'email_preferences.price_drops'      => 'sometimes|boolean',
            'email_preferences.review_requests'  => 'sometimes|boolean',
            'email_preferences.account_updates'  => 'sometimes|boolean',
            'email_preferences.low_stock_alerts' => 'sometimes|boolean',
            'email_preferences.cart_reminders'   => 'sometimes|boolean',
            'mobile_number'                      => 'sometimes|nullable|string|max:20',
        ]);

        $user = $request->user();

        if ($request->has('email_preferences')) {
            $current = $user->email_preferences ?? NotificationService::getDefaultPreferences();
            $user->email_preferences = array_merge($current, $request->input('email_preferences'));
        }

        if ($request->has('mobile_number')) {
            $user->mobile_number = $request->input('mobile_number');
        }

        $user->save();

        return response()->json([
            'message'           => 'Preferences updated successfully.',
            'email_preferences' => $user->email_preferences,
            'mobile_number'     => $user->mobile_number,
        ]);
    }
}
