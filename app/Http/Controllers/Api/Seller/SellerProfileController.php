<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SellerProfileController extends Controller
{
    /**
     * Show the current seller's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar 
                    ? asset('storage/' . $user->avatar) 
                    : null,
                'role' => $user->getRoleNames()->first() ?? 'Seller',
                'created_at' => $user->created_at->format('M d, Y'),
            ]
        ]);
    }

    /**
     * Update the profile information (name only).
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
        ]);

        $user->update(['name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar 
                    ? asset('storage/' . $user->avatar) 
                    : null,
                'role' => $user->getRoleNames()->first() ?? 'Seller',
            ]
        ]);
    }

    /**
     * Upload and update the user's avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar' => asset('storage/' . $path),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first() ?? 'Seller',
            ]
        ]);
    }

    /**
     * Change the user's password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Re-submit a rejected application.
     */
    public function reApply(Request $request)
    {
        $user = $request->user();
        
        if ($user->seller_status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Only rejected applications can be re-submitted',
            ], 422);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:100',
            'store_description' => 'required|string|max:500',
            'business_type' => 'required|string|max:50',
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
        ]);

        $user->update([
            'store_name' => $validated['store_name'],
            'store_description' => $validated['store_description'],
            'business_type' => $validated['business_type'],
            'seller_status' => 'pending',
            'rejection_reason' => null, // Clear the reason
        ]);

        // Use standard categories relationship
        if (method_exists($user, 'categories')) {
            $user->categories()->sync($validated['categories']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Application re-submitted successfully',
            'data' => $user
        ]);
    }
}
