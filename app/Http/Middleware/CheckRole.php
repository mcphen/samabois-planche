<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage: middleware('role:admin') ou middleware('role:admin,comptable')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès refusé.'], 403);
            }

            // Rediriger vers la première page autorisée selon le rôle
            if ($user && $user->role === 'comptable') {
                return redirect()->route('planches.index')->with('error', 'Accès refusé.');
            }

            abort(403, 'Accès refusé.');
        }

        return $next($request);
    }
}
