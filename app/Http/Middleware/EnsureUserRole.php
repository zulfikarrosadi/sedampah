<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = $user ? strtolower(trim((string) $user->role)) : null;
        $allowedRoles = array_map(fn (string $r) => strtolower(trim($r)), $roles);

        if (!$user || !in_array($userRole, $allowedRoles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access for your account role.',
                    'data' => null,
                ], 403);
            }

            abort(403, 'Unauthorized access for your account role.');
        }

        return $next($request);
    }
}
