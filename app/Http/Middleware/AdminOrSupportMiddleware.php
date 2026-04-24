<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrSupportMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ($request->user()->isAdmin() || $request->user()->isSupport())) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied. Admins or support staff only.'
        ], 403);
    }
}
