<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('permission:leads.view')
     *   ->middleware('permission:deals.create,deals.edit')  // any of these
     *
     * @param string $permissions  Comma-separated permission slugs (user needs ANY one)
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request);
        }

        // Owner bypasses all permission checks
        if ($user->owner) {
            return $next($request);
        }

        // Check if user has ANY of the listed permissions
        $permissionList = explode(',', $permissions);

        foreach ($permissionList as $permission) {
            if ($user->hasPermission(trim($permission))) {
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
                'message' => 'Bạn không có quyền truy cập. / You do not have permission.',
            ], 403);
        }

        abort(403, 'Bạn không có quyền truy cập.');
    }
}
