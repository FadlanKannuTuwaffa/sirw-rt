<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResolveAssistantThread
{
    public function handle(Request $request, Closure $next)
    {
        $threadId = $this->determineThreadId($request);
        $timezone = $this->determineTimezone($request);

        $request->attributes->set('assistant_thread_id', $threadId);
        if ($timezone !== null) {
            $request->attributes->set('assistant_timezone', $timezone);
        }

        if ($request->hasSession()) {
            $request->session()->put('assistant_thread_id', $threadId);
            if ($timezone !== null) {
                $request->session()->put('assistant_timezone', $timezone);
            }
        }

        return $next($request);
    }

    private function determineThreadId(Request $request): string
    {
        $threadId = (string) $request->input('thread_id', '');

        if ($threadId === '') {
            $threadId = (string) $request->header('X-Assistant-Thread', '');
        }

        if ($threadId === '' && $request->hasSession()) {
            $threadId = (string) $request->session()->get('assistant_thread_id', '');
        }

        if ($threadId === '') {
            $threadId = $request->user()
                ? 'user:' . $request->user()->getKey()
                : 'session:' . ($request->session()->getId() ?: Str::uuid()->toString());
        }

        $threadId = Str::of($threadId)
            ->replaceMatches('/[^a-zA-Z0-9_\-:]/', '')
            ->limit(64, '')
            ->value();

        if ($threadId === '') {
            $threadId = Str::substr(Str::uuid()->toString(), 0, 32);
        }

        return $threadId;
    }

    private function determineTimezone(Request $request): ?string
    {
        $candidates = [
            $request->input('timezone'),
            $request->header('X-Assistant-Timezone'),
            $request->query('timezone'),
        ];

        if ($request->hasSession()) {
            $candidates[] = $request->session()->get('assistant_timezone');
        }

        $user = $request->user();
        if ($user) {
            $preferences = $user->experience_preferences ?? [];
            $candidates[] = $preferences['timezone'] ?? null;
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeTimezone($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeTimezone(mixed $timezone): ?string
    {
        if (!is_string($timezone)) {
            return null;
        }

        $candidate = trim($timezone);

        if ($candidate === '') {
            return null;
        }

        try {
            new \DateTimeZone($candidate);

            return $candidate;
        } catch (\Throwable) {
            return null;
        }
    }
}
