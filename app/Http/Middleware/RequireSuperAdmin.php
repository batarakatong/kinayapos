<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya super_admin yang bisa akses admin panel routes.
 */
class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Cek apakah user punya role super_admin di branch manapun
        $isSuperAdmin = $user->branches()
            ->wherePivot('role', 'super_admin')
            ->exists();

        if (!$isSuperAdmin) {
            return response()->json(['message' => 'Super admin access required'], 403);
        }

        return $next($request);
    }
}
