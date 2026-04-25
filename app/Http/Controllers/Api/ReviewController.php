<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/products/{productId}/reviews
     */
    public function index(Request $request, $productId)
    {
        $filters = $request->only(['rating', 'verified_only', 'keyword']);
        $sort    = $request->input('sort', 'newest');
        $perPage = (int) $request->input('per_page', 5);
        $page    = $request->input('page', 1);

        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        
        $cacheKey = "product_{$productId}_reviews_p{$page}_s{$sort}_pp{$perPage}_" . md5(json_encode($filters)) . "_{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request, $productId, $filters, $sort, $perPage, $user) {
            $reviews = $this->reviewService->getProductReviews($productId, $filters, $sort, $perPage);
            $stats   = $this->reviewService->getRatingStats($productId);

            // Check if current user has reviewed this product
            $userReview = null;
            if ($user) {
                $userReview = Review::where('product_id', $productId)
                    ->where('user_id', $user->id)
                    ->first();
            }

            // Check if current user's votes on these reviews
            $userVotes = [];
            if ($user) {
                $reviewIds = $reviews->pluck('id')->toArray();
                $userVotes = \App\Models\ReviewVote::where('user_id', $user->id)
                    ->whereIn('review_id', $reviewIds)
                    ->pluck('vote', 'review_id')
                    ->toArray();
            }

            return response()->json([
                'reviews'     => $reviews->items(),
                'pagination'  => [
                    'current_page' => $reviews->currentPage(),
                    'last_page'    => $reviews->lastPage(),
                    'per_page'     => $reviews->perPage(),
                    'total'        => $reviews->total(),
                ],
                'stats'       => $stats,
                'user_review' => $userReview,
                'user_votes'  => $userVotes,
            ]);
        });
    }

    /**
     * POST /api/products/{productId}/reviews
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'required|string|min:10|max:2000',
            'photos'   => 'sometimes|array|max:5',
            'photos.*' => 'image|max:5120', // 5MB
        ]);

        try {
            $review = $this->reviewService->createReview(
                $request->user()->id,
                $productId,
                $request->all()
            );

            Cache::flush(); // Simple flush for reviews as they affect many keys

            return response()->json([
                'message' => 'Review submitted successfully! It will be visible after approval.',
                'review'  => $review->load('user:id,name'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /api/reviews/{id}
     */
    public function update(Request $request, $id)
    {
        $review = Review::where('user_id', $request->user()->id)->findOrFail($id);

        $request->validate([
            'rating'  => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|min:10|max:2000',
        ]);

        $review->update($request->only(['rating', 'comment']));
        $review->update(['status' => Review::STATUS_PENDING]); // re-approve after edit

        Cache::flush();

        return response()->json([
            'message' => 'Review updated and resubmitted for approval.',
            'review'  => $review->fresh()->load('user:id,name'),
        ]);
    }

    /**
     * DELETE /api/reviews/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        // Only owner or admin can delete
        if ($review->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $review->delete();
        Cache::flush();
        return response()->json(['message' => 'Review deleted.']);
    }

    /**
     * POST /api/reviews/{id}/upvote
     */
    public function upvote(Request $request, $id)
    {
        $review = $this->reviewService->vote($id, $request->user()->id, 'upvote');
        return response()->json(['upvotes' => $review->upvotes, 'downvotes' => $review->downvotes]);
    }

    /**
     * POST /api/reviews/{id}/downvote
     */
    public function downvote(Request $request, $id)
    {
        $review = $this->reviewService->vote($id, $request->user()->id, 'downvote');
        return response()->json(['upvotes' => $review->upvotes, 'downvotes' => $review->downvotes]);
    }

    /**
     * PUT /api/admin/reviews/{id}/approve
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => Review::STATUS_APPROVED]);
        return response()->json(['message' => 'Review approved.', 'review' => $review]);
    }

    /**
     * PUT /api/admin/reviews/{id}/reject
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => Review::STATUS_REJECTED]);
        return response()->json(['message' => 'Review rejected.', 'review' => $review]);
    }

    /**
     * GET /api/admin/reviews — all reviews for admin
     */
    public function adminIndex(Request $request)
    {
        $filters = $request->only(['status', 'product_id', 'rating']);
        $reviews = $this->reviewService->getAllReviews($filters);
        return response()->json($reviews);
    }
}
