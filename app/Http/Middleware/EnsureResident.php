<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureResident
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'warga') {
            abort(403);
        }

        $preferredLocale = data_get(
            $user->experience_preferences,
            'language',
            config('app.locale', 'id')
        );

        $locale = in_array($preferredLocale, ['id', 'en'], true) ? $preferredLocale : 'id';

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
