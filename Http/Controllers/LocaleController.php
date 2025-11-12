<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    private const SUPPORTED = ['id', 'en'];
    private const COOKIE_NAME = 'sirw_locale';
    private const COOKIE_MINUTES = 525600; // 1 year

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! in_array(strtolower($value), self::SUPPORTED, true)) {
                        $fail('Unsupported locale.');
                    }
                }
            ],
        ]);

        $locale = strtolower($data['locale']);
        $this->applyLocale($locale);
        Cookie::queue(self::COOKIE_NAME, $locale, self::COOKIE_MINUTES);

        return response()->json([
            'locale' => $locale,
            'message' => 'Locale updated.',
        ]);
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
