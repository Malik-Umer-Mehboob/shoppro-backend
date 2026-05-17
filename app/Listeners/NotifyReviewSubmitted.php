<?php

namespace App\Listeners;

use App\Events\ReviewSubmitted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyReviewSubmitted implements ShouldQueue
{
    public function handle(ReviewSubmitted $event): void
    {
        $review = $event->review;
        $product = $review->product;
        $seller = $product->seller;

        // Notify Admins
        NotificationService::notifyAdmins(
            'New Product Review! ⭐',
            "User {$review->user->name} reviewed '{$product->name}': {$review->rating}/5 stars.",
            'review.new',
            NotificationService::PRIORITY_MEDIUM,
            ['review_id' => $review->id],
            '/admin/reviews'
        );

        // Notify Seller if applicable
        if ($seller) {
            NotificationService::send(
                $seller->id,
                'review.new',
                'New Product Review! ⭐',
                "Your product '{$product->name}' received a {$review->rating}-star review.",
                ['review_id' => $review->id],
                NotificationService::PRIORITY_MEDIUM,
                '/seller/reviews'
            );
        }
    }
}
