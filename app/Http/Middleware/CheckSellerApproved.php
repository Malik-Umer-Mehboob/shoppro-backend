<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSellerApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('seller')) {
            if ($user->seller_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your seller account is ' . $user->seller_status . '. Access restricted.',
                    'seller_status' => $user->seller_status,
                ], 403);
            }
        }

        return $next($request);
    }
}
