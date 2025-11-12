<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAssistantCache extends Command
{
    protected $signature = 'assistant:clear-cache {--type=all : Type of cache to clear (all, bills, agenda, contacts, rag)}';
    protected $description = 'Clear assistant-related cache';

    public function handle(): int
    {
        $type = $this->option('type');
        
        $cleared = match($type) {
            'bills' => $this->clearPattern('bills_*'),
            'agenda' => $this->clearPattern('agenda_*'),
            'contacts' => $this->clearPattern('rt_contacts_*'),
            'rag' => $this->clearPattern('rag_search_*'),
            'all' => $this->clearAll(),
            default => 0,
        };
        
        $this->info("Cleared {$cleared} cache entries for type: {$type}");
        return 0;
    }

    private function clearPattern(string $pattern): int
    {
        $count = 0;
        $keys = Cache::get('cache_keys', []);
        
        foreach ($keys as $key) {
            if (fnmatch($pattern, $key)) {
                Cache::forget($key);
                $count++;
            }
        }
        
        return $count;
    }

    private function clearAll(): int
    {
        Cache::flush();
        return 1;
    }
}
