<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewVote;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * Create a review for a product.
     */
    public function createReview(int $userId, int $productId, array $data): Review
    {
        // Check if user already reviewed this product
        $existing = Review::where('user_id', $userId)->where('product_id', $productId)->first();
        if ($existing) {
            throw new \Exception('You have already reviewed this product.');
        }

        // Check verified purchase
        $verifiedPurchase = Order::where('user_id', $userId)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereHas('items', fn($q) => $q->where('product_id', $productId))
            ->exists();

        // Handle photo uploads
        $photos = [];
        if (!empty($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                if ($photo instanceof UploadedFile) {
                    $photos[] = $photo->store('reviews', 'public');
                }
            }
        }

        return Review::create([
            'product_id'        => $productId,
            'user_id'           => $userId,
            'rating'            => $data['rating'],
            'comment'           => $data['comment'],
            'photos'            => !empty($photos) ? $photos : null,
            'verified_purchase' => $verifiedPurchase,
            'status'            => Review::STATUS_PENDING,
        ]);
    }

    /**
     * Get reviews for a product with filters, sorting, pagination.
     */
    public function getProductReviews(int $productId, array $filters = [], string $sort = 'newest', int $perPage = 5): LengthAwarePaginator
    {
        $query = Review::forProduct($productId)->approved()->with(['user:id,name']);

        // Filter by rating
        if (!empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        // Verified purchase only
        if (!empty($filters['verified_only'])) {
            $query->where('verified_purchase', true);
        }

        // Keyword search in comments
        if (!empty($filters['keyword'])) {
            $query->where('comment', 'like', '%' . $filters['keyword'] . '%');
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'highest':
                $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'most_helpful':
                $query->orderByRaw('(upvotes - downvotes) DESC')->orderBy('created_at', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get rating distribution for a product.
     */
    public function getRatingStats(int $productId): array
    {
        $reviews = Review::forProduct($productId)->approved();

        $total = $reviews->count();
        $average = round($reviews->avg('rating') ?? 0, 1);

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = (clone $reviews)->where('rating', $i)->count();
            $distribution[$i] = [
                'stars'      => $i,
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100) : 0,
            ];
        }

        return [
            'average'      => $average,
            'total'        => $total,
            'distribution' => array_values($distribution),
        ];
    }

    /**
     * Vote on a review (upvote or downvote).
     */
    public function vote(int $reviewId, int $userId, string $voteType): Review
    {
        $review = Review::findOrFail($reviewId);
        $existing = ReviewVote::where('review_id', $reviewId)->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->vote === $voteType) {
                // Remove vote (toggle off)
                $existing->delete();
                $voteType === 'upvote' ? $review->decrement('upvotes') : $review->decrement('downvotes');
            } else {
                // Switch vote
                $oldType = $existing->vote;
                $existing->update(['vote' => $voteType]);
                $oldType === 'upvote' ? $review->decrement('upvotes') : $review->decrement('downvotes');
                $voteType === 'upvote' ? $review->increment('upvotes') : $review->increment('downvotes');
            }
        } else {
            ReviewVote::create([
                'review_id' => $reviewId,
                'user_id'   => $userId,
                'vote'      => $voteType,
            ]);
            $voteType === 'upvote' ? $review->increment('upvotes') : $review->increment('downvotes');
        }

        return $review->fresh();
    }

    /**
     * Admin: get all reviews with filters.
     */
    public function getAllReviews(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Review::with(['user:id,name', 'product:id,name,slug,thumbnail']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
