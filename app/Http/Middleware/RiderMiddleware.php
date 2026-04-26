<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RiderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->hasRole('rider')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Rider only.',
            ], 403);
        }
        return $next($request);
    }
}
