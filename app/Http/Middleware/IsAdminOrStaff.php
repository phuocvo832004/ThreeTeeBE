<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminOrStaff
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isStaff())) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}