<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        
        // Support multiple roles separated by pipe (e.g., role:admin|support)
        $allowedRoles = explode('|', $role);
        
        if (!$user || !in_array($user->role, $allowedRoles)) {
            abort(403);
        }
        
        return $next($request);
    }
}
