<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupportMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isSupport()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized Access'
        ], 403);
    }
}
