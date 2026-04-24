<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CompleteReferralListener
{
    protected $referralService;

    /**
     * Create the event listener.
     */
    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        // Check if user was referred
        $referral = Referral::where('referee_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            // Check if this is their first order
            $orderCount = $user->orders()->count();
            if ($orderCount === 1) {
                $this->referralService->completeReferral($referral);
            }
        }
    }
}
