<?php

namespace App\Console\Commands;

use App\Models\KbChunk;
use App\Services\Assistant\Retrieval\HybridRetriever;
use Illuminate\Console\Command;

class RagSyncIndexes extends Command
{
    protected $signature = 'rag:sync {--truncate : Truncate remote indexes before syncing}';

    protected $description = 'Sync KB chunks into Meilisearch (BM25) and Qdrant (vector) indexes.';

    public function handle(HybridRetriever $retriever): int
    {
        if (!$retriever->isConfigured()) {
            $this->warn('Hybrid retriever is not configured. Set RAG_MEILI_* and RAG_QDRANT_* env vars first.');

            return Command::FAILURE;
        }

        if ($this->option('truncate')) {
            $retriever->flushIndexes();
            $this->info('Remote indexes truncated.');
        }

        $chunks = KbChunk::with('article')->orderBy('id')->get();

        if ($chunks->isEmpty()) {
            $this->warn('No KB chunks found. Run kb:ingest first.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($chunks->count());

        foreach ($chunks as $chunk) {
            $document = [
                'id' => $chunk->id,
                'article_id' => $chunk->article_id,
                'title' => $chunk->article?->title ?? ('Artikel ' . $chunk->article_id),
                'content' => $chunk->chunk_text,
            ];

            $embedding = is_array($chunk->embedding) ? $chunk->embedding : null;

            $retriever->indexDocument($document, $embedding);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Hybrid retrieval indexes synchronized.');

        return Command::SUCCESS;
    }
}
