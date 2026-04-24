<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReferralSignupListener
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
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;
        $code = $event->referralCode;

        if ($code) {
            $referral = Referral::where('referral_code', $code)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();

            if ($referral) {
                $this->referralService->linkReferee($referral, $user);
            }
        }
    }
}
