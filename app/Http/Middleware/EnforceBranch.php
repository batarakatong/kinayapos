<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $branchId = (int) $request->header('X-Branch-ID', 0);
        $user = $request->user();

        if ($branchId === 0) {
            return response()->json(['message' => 'X-Branch-ID header required'], 400);
        }

        if (!$user || !$user->hasBranchAccess($branchId)) {
            return response()->json(['message' => 'Branch access denied'], 403);
        }

        // Share branch_id for controllers
        $request->attributes->set('branch_id', $branchId);

        return $next($request);
    }
}
