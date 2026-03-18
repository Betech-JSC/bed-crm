<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,sales_manager')  // any of these
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        if ($user->owner) {
            return $next($request);
        }

        $roleList = explode(',', $roles);

        foreach ($roleList as $role) {
            if ($user->hasRole(trim($role))) {
                return $next($request);
            }
        }

        return $this->unauthorized($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Bạn không có vai trò phù hợp. / Insufficient role.',
            ], 403);
        }

        abort(403, 'Bạn không có vai trò phù hợp.');
    }
}
