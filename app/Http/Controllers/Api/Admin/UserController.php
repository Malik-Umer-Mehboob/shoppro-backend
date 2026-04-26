<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // List all users
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            });

        // Filter by role
        if ($request->role && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->status === 'blocked') {
            $query->where('is_blocked', true);
        } elseif ($request->status === 'active') {
            $query->where('is_blocked', false);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(15);

        $mapped = $users->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'avatar' => $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : null,
                'is_blocked' => $user->is_blocked,
                'block_reason' => $user->block_reason,
                'blocked_at' => $user->blocked_at?->format('M d, Y'),
                'created_at' => $user->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }

    // Block a user
    public function block(Request $request, $userId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        // Cannot block admin
        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot block admin account',
            ], 422);
        }

        $user->update([
            'is_blocked' => true,
            'block_reason' => $request->reason ?? 'Blocked by administrator',
            'blocked_at' => now(),
        ]);

        ActivityLog::log('user.blocked',
            "User {$user->name} was blocked",
            'User', $userId
        );

        // Revoke all tokens (force logout)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been blocked",
        ]);
    }

    // Unblock a user
    public function unblock($userId)
    {
        $user = User::findOrFail($userId);

        $user->update([
            'is_blocked' => false,
            'block_reason' => null,
            'blocked_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been unblocked",
        ]);
    }

    // Get single user details
    public function show($userId)
    {
        $user = User::with('roles')->findOrFail($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'is_blocked' => $user->is_blocked,
                'block_reason' => $user->block_reason,
                'created_at' => $user->created_at->format('M d, Y'),
                'total_orders' => $user->orders()->count() ?? 0,
            ]
        ]);
    }
}
