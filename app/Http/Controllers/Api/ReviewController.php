<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    /**
     * GET /api/products/{productId}/reviews
     * Public index for product reviews (approved only)
     */
    public function index($productId)
    {
        $reviews = Review::with('user:id,name,avatar')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->latest()
            ->paginate(10);

        $mapped = $reviews->through(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'verified_purchase' => $review->verified_purchase,
                'is_approved' => $review->is_approved,
                'reviewer_name' => $review->user->name ?? 'Anonymous',
                'reviewer_avatar' => $review->user->avatar 
                    ? asset('storage/' . $review->user->avatar) 
                    : null,
                'created_at' => $review->created_at->format('M d, Y'),
            ];
        });

        // Average rating
        $avgRating = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->avg('rating');

        $totalReviews = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $mapped,
                'average_rating' => round($avgRating ?? 0, 1),
                'total_reviews' => $totalReviews,
            ]
        ]);
    }

    /**
     * POST /api/products/{productId}/reviews
     * Store a new review
     */
    public function store(Request $request, $productId)
    {
        $user = auth()->user();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product',
            ], 422);
        }

        // Check if user has purchased this product (verified purchase)
        $hasPurchased = OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', 'delivered');
            })
            ->where('product_id', $productId)
            ->exists();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'verified_purchase' => $hasPurchased,
            'is_approved' => false,
        ]);

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => $hasPurchased 
                ? 'Review submitted with Verified Purchase badge!' 
                : 'Review submitted and awaiting approval',
            'data' => $review,
        ]);
    }

    /**
     * GET /api/admin/reviews
     * List all reviews for moderation
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['user:id,name,email', 'product:id,name,thumbnail']);

        if ($request->status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($request->status === 'approved') {
            $query->where('is_approved', true);
        }

        $reviews = $query->latest()->paginate(15);

        $mapped = $reviews->through(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'verified_purchase' => $review->verified_purchase,
                'is_approved' => $review->is_approved,
                'reviewer_name' => $review->user->name ?? 'Unknown',
                'reviewer_email' => $review->user->email ?? '',
                'product_name' => $review->product->name ?? 'Unknown',
                'product_thumbnail' => $review->product->thumbnail 
                    ? asset('storage/' . $review->product->thumbnail) 
                    : null,
                'created_at' => $review->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }

    /**
     * POST /api/admin/reviews/{id}/approve
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update([
            'is_approved' => true,
            'approved_at' => now(),
            'status' => 'approved' // Keeping compatibility with existing status column
        ]);

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully',
        ]);
    }

    /**
     * DELETE /api/admin/reviews/{id}/reject
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Review rejected and deleted',
        ]);
    }

    /**
     * Existing functionality: update review
     */
    public function update(Request $request, $id)
    {
        $review = Review::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'rating'  => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|min:10|max:1000',
        ]);

        $review->update($request->only(['rating', 'comment']));
        $review->update([
            'is_approved' => false,
            'status' => 'pending'
        ]);

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Review updated and resubmitted for approval.',
            'data'  => $review->fresh()->load('user:id,name'),
        ]);
    }

    /**
     * Existing functionality: delete review
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $review->delete();
        Cache::flush();
        return response()->json(['success' => true, 'message' => 'Review deleted.']);
    }
}
