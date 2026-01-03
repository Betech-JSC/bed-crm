<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateChatWidgetOrigin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow requests from any origin for chat widget (CORS)
        $response = $next($request);

        // Set CORS headers
        $origin = $request->header('Origin');
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Origin, X-Requested-With');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, $response->headers->all());
        }

        return $response;
    }
}
