<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'warga') {
            Auth::logout();

            return redirect()->route('login');
        }

        if ($user->email_verified_at === null) {
            return redirect()->route('resident.verification.notice');
        }

        return $next($request);
    }
}
