<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale') ?: $request->get('lang');

        if (!$locale) {
            $locale = $request->getPreferredLanguage(['en', 'es', 'fr', 'ar']); // Examples
        }

        if ($locale) {
            // Validate if locale exists in our DB or just set it
            // For now, let's just set it if it's a 2-character code
            if (strlen($locale) >= 2) {
                App::setLocale(substr($locale, 0, 2));
            }
        }

        return $next($request);
    }
}
