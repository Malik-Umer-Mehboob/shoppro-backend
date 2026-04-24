<?php

namespace App\Http\Middleware;

use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AffiliateTrackingMiddleware
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $affCode = $request->query('ref') ?: $request->query('aff');

        if ($affCode) {
            $affiliate = $this->affiliateService->getAffiliateByCode($affCode);

            if ($affiliate) {
                // Track click
                $this->affiliateService->trackClick($affiliate->id, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->header('referer'),
                    'landing' => $request->fullUrl(),
                ]);

                // Store in cookie for 30 days
                Cookie::queue('affiliate_id', $affiliate->id, 60 * 24 * 30);
            }
        }

        return $next($request);
    }
}
