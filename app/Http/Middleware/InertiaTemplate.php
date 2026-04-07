<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Middleware;
class InertiaTemplate
{

    public function handle($request, Closure $next)
    {
        Inertia::setRootView(auth()->check() ? 'app' : 'guest');
        return $next($request);
    }
}
