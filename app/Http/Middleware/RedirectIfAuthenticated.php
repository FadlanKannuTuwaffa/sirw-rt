<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($request->routeIs('login', 'register')) {
                    return $next($request);
                }

                $user = Auth::guard($guard)->user();
                $route = $user && $user->isAdmin() ? route('admin.dashboard') : route('resident.dashboard');

                return redirect()->intended($route);
            }
        }

        return $next($request);
    }
}
