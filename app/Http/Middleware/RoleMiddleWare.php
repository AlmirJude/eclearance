<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            abort(403, 'Not authenticated');
        }


        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'Unauthorized role. Your role: ' . Auth::user()->role . '. Allowed: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}
