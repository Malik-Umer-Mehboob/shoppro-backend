<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller
{
    // Get all active sessions/devices
    public function index(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $devices = $user->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(function ($token) use ($currentToken) {
                return [
                    'id' => $token->id,
                    'device_name' => $token->device_name ?? 'Unknown Device',
                    'device_type' => $token->device_type ?? 'desktop',
                    'ip_address' => $token->ip_address ?? 'Unknown',
                    'last_used_at' => $token->last_used_at
                        ? $token->last_used_at->diffForHumans()
                        : $token->created_at->diffForHumans(),
                    'is_current' => $token->id === $currentToken->id,
                    'created_at' => $token->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    // Logout specific device
    public function destroy(Request $request, $tokenId)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken->id == $tokenId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke current session. Use logout instead.',
            ], 422);
        }

        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Device session not found',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device logged out successfully',
        ]);
    }

    // Logout ALL other devices
    public function logoutAllOthers(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All other devices logged out successfully',
        ]);
    }

    // Logout ALL devices including current
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }
}
