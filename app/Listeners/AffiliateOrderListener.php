<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cookie;

class AffiliateOrderListener
{
    protected $affiliateService;

    /**
     * Create the event listener.
     */
    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $affiliateId = Cookie::get('affiliate_id');

        if ($affiliateId) {
            $affiliate = Affiliate::find($affiliateId);
            
            if ($affiliate && $affiliate->status === 'active') {
                $this->affiliateService->calculateCommission($order, $affiliate);
            }
        }
    }
}
