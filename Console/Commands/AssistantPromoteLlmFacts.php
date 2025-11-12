<?php

namespace App\Console\Commands;

use App\Jobs\PromoteLlmSnapshot;
use App\Models\AssistantLlmSnapshot;
use Illuminate\Console\Command;

class AssistantPromoteLlmFacts extends Command
{
    protected $signature = 'assistant:promote-llm-facts {--batch=25 : Number of snapshots to queue per run}';

    protected $description = 'Queue helpful LLM snapshots for promotion into corrections/knowledge base';

    public function handle(): int
    {
        if (!config('assistant.features.llm_promotion', true)) {
            $this->info('LLM promotion disabled via feature flag.');

            return self::SUCCESS;
        }

        $batch = max(1, (int) $this->option('batch'));

        $snapshots = AssistantLlmSnapshot::query()
            ->where('is_helpful', true)
            ->where('auto_promote_ready', true)
            ->whereIn('promotion_status', ['pending', 'retry'])
            ->orderBy('id')
            ->limit($batch)
            ->get();

        if ($snapshots->isEmpty()) {
            $this->info('No helpful snapshots waiting for promotion.');

            $pending = AssistantLlmSnapshot::query()
                ->where('is_helpful', true)
                ->where('auto_promote_ready', false)
                ->whereIn('promotion_status', ['pending', 'retry'])
                ->count();

            if ($pending > 0) {
                $this->line("{$pending} snapshot belum memenuhi syarat (butuh >=2 feedback positif atau label evaluasi PASS).");
            }

            return self::SUCCESS;
        }

        foreach ($snapshots as $snapshot) {
            PromoteLlmSnapshot::dispatch($snapshot->id);
            $snapshot->promotion_status = 'queued';
            $snapshot->save();
        }

        $this->info('Queued ' . $snapshots->count() . ' snapshots for promotion.');

        return self::SUCCESS;
    }
}
