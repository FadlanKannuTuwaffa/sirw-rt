<?php

namespace App\Http\Middleware;

use App\Jobs\DynamicReminderDispatcher;
use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as CacheFacade;
use Illuminate\Support\Facades\Log;

class DispatchDueReminders
{
    public function __construct(private ?Cache $cache = null)
    {
        $this->cache = $cache ?: CacheFacade::store();
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        Log::debug('DispatchDueReminders invoked');

        $this->runDispatcherIfNeeded();

        return $response;
    }

    private function runDispatcherIfNeeded(): void
    {
        $cacheKey = 'reminder_dispatcher_last_run';
        $lockName = 'reminder_dispatcher_lock';

        if ($this->cache->has($cacheKey) && now()->diffInSeconds($this->cache->get($cacheKey)) < 55) {
            Log::debug('Reminder dispatcher skipped (cooldown active)');
            return;
        }

        $this->cache->lock($lockName, 5)->get(function () use ($cacheKey) {
            try {
                Log::info('Reminder dispatcher running now');
                DynamicReminderDispatcher::dispatchSync();
            } catch (\Throwable $e) {
                Log::error('Reminder dispatcher failed', ['error' => $e->getMessage()]);
            }

            $this->cache->put($cacheKey, now(), 60);
        });
    }
}
