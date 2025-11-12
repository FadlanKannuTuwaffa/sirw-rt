<?php

namespace App\Observers;

use App\Models\KbChunk;
use App\Services\Assistant\Retrieval\HybridRetriever;

class KbChunkObserver
{
    public function __construct(private HybridRetriever $retriever)
    {
    }

    public function created(KbChunk $chunk): void
    {
        $this->syncChunk($chunk);
    }

    public function updated(KbChunk $chunk): void
    {
        $this->syncChunk($chunk);
    }

    public function deleted(KbChunk $chunk): void
    {
        if (!$this->retriever->isConfigured()) {
            return;
        }

        $this->retriever->deleteDocument($chunk->getKey());
    }

    private function syncChunk(KbChunk $chunk): void
    {
        if (!$this->retriever->isConfigured()) {
            return;
        }

        $chunk->loadMissing('article');

        $this->retriever->indexDocument([
            'id' => $chunk->getKey(),
            'article_id' => $chunk->article_id,
            'title' => $chunk->article?->title ?? 'Dokumen',
            'content' => $chunk->chunk_text,
        ], is_array($chunk->embedding) ? $chunk->embedding : null);
    }
}

