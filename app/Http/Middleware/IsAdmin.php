<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika belum login atau bukan admin -> 403
        if (!Auth::check() || (Auth::user()?->role !== 'admin')) {
            abort(403);
        }

        return $next($request);
    }
}
