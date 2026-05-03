<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_contains($request->header('Accept-Encoding', ''), 'gzip') && function_exists('gzencode')) {
            $response->setContent(gzencode($response->getContent(), 9));
            $response->headers->set('Content-Encoding', 'gzip');
        }

        return $response;
    }
}
