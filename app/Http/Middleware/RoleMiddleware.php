<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Owner/Admin always has access to everything
        if ($user->isOwner()) {
            return $next($request);
        }

        if (!$user->hasRole($roles)) {
            return response()->json([
                'message' => 'Unauthorized. Your role (' . $user->role . ') does not have access to this resource.',
            ], 403);
        }

        return $next($request);
    }
}
