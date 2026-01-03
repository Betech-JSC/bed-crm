<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from session, query parameter, or use default
        $locale = $request->query('lang') 
            ?? Session::get('locale') 
            ?? config('app.locale', 'vi');

        // Validate locale (only allow 'vi' or 'en')
        if (!in_array($locale, ['vi', 'en'])) {
            $locale = 'vi';
        }

        // Set locale
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
