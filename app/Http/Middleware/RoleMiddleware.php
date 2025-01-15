<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (auth('api')->user()->role !== $role) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}