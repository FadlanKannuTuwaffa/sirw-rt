<?php

namespace App\Console\Commands;

use App\Services\Assistant\RAGService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class KbIngest extends Command
{
    protected $signature = 'kb:ingest {--clear : Clear existing articles first}';
    protected $description = 'Ingest markdown files from storage/app/kb into knowledge base';

    public function handle(RAGService $rag): int
    {
        if ($this->option('clear')) {
            \App\Models\KbArticle::truncate();
            $this->info('Cleared existing knowledge base.');
        }

        $files = Storage::files('kb');
        
        if (empty($files)) {
            $this->warn('No files found in storage/app/kb/');
            $this->info('Create .md files there with FAQ/SOP content.');
            return 0;
        }

        $this->info('Ingesting ' . count($files) . ' files...');
        $bar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            if (!str_ends_with($file, '.md')) {
                continue;
            }

            $content = Storage::get($file);
            $title = basename($file, '.md');
            
            $rag->ingest($title, $content);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Knowledge base ingestion completed!');

        return 0;
    }
}
