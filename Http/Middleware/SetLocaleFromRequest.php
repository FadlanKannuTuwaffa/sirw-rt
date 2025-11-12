<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequest
{
    private const SUPPORTED = ['id', 'en'];
    private const COOKIE_NAME = 'sirw_locale';
    private const COOKIE_MINUTES = 525600; // 1 year
    private const DEFAULT_LOCALE = 'id';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferredLocale($request);
        $normalized = strtolower($locale);
        $this->applyLocale($normalized);
        Log::info('Determined locale: ' . $normalized);

        /** @var Response $response */
        $response = $next($request);

        if ($request->cookies->get(self::COOKIE_NAME) !== $normalized) {
            cookie()->queue(cookie(self::COOKIE_NAME, $normalized, self::COOKIE_MINUTES));
        }

        return $response;
    }

    private function preferredLocale(Request $request): string
    {
        // Check authenticated user's preference first
        if (Auth::check()) {
            $user = Auth::user();
            $userPreferences = $user->experience_preferences ?? [];
            $userLocale = Arr::get($userPreferences, 'language');
            Log::info('User locale from preferences: ' . $userLocale);
            if ($this->isSupported($userLocale)) {
                return strtolower($userLocale);
            }
        }

        $queryLocale = $request->query('lang');
        if ($this->isSupported($queryLocale)) {
            return strtolower($queryLocale);
        }

        $cookieLocale = $request->cookies->get(self::COOKIE_NAME);
        if ($this->isSupported($cookieLocale)) {
            return strtolower($cookieLocale);
        }

        return self::DEFAULT_LOCALE;
    }

    private function isSupported(?string $locale): bool
    {
        if (! $locale) {
            return false;
        }

        return in_array(strtolower($locale), self::SUPPORTED, true);
    }

    private function applyLocale(string $locale): void
    {
        app()->setLocale($locale);
        Config::set('app.locale', $locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        $phpLocales = match ($locale) {
            'id' => ['id_ID.UTF-8', 'id_ID', 'id', 'IND'],
            'en' => ['en_US.UTF-8', 'en_US', 'en', 'ENG'],
            default => [$locale . '.UTF-8', $locale],
        };
        @setlocale(LC_TIME, ...$phpLocales);
    }
}
