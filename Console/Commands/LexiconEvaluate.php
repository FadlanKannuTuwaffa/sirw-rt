<?php

namespace App\Console\Commands;

use App\Services\Assistant\LexiconService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LexiconEvaluate extends Command
{
    protected $signature = 'assistant:lexicon-evaluate {--dataset=tests/data/lexicon_eval.json} {--refresh : Refresh DB lexicon cache before running}';

    protected $description = 'Evaluate LexiconService fuzzy correction using a curated dataset.';

    public function handle(LexiconService $lexicon): int
    {
        $datasetPath = base_path($this->option('dataset'));

        if (!File::exists($datasetPath)) {
            $this->error("Dataset not found at {$datasetPath}");
            return Command::FAILURE;
        }

        $payload = json_decode(File::get($datasetPath), true);

        if (!is_array($payload) || $payload === []) {
            $this->error('Dataset is empty or invalid JSON.');
            return Command::FAILURE;
        }

        if ($this->option('refresh')) {
            $lexicon->refreshDatabaseLexicon(true);
        }

        $result = $lexicon->evaluateDataset($payload);

        $this->table(
            ['Input', 'Predicted', 'Expected', 'TP', 'FP', 'FN'],
            collect($result['details'] ?? [])->map(function (array $detail) {
                return [
                    $detail['input'] ?? '',
                    implode(', ', $detail['predicted'] ?? []),
                    implode(', ', $detail['expected'] ?? []),
                    $detail['tp'] ?? 0,
                    $detail['fp'] ?? 0,
                    $detail['fn'] ?? 0,
                ];
            })->all()
        );

        $this->info('Cases        : ' . ($result['cases'] ?? 0));
        $this->info('Exact match  : ' . number_format(($result['exact_match'] ?? 0) * 100, 2) . '%');
        $this->info('Precision    : ' . number_format(($result['precision'] ?? 0) * 100, 2) . '%');
        $this->info('Recall       : ' . number_format(($result['recall'] ?? 0) * 100, 2) . '%');
        $this->info('F1 Score     : ' . number_format(($result['f1'] ?? 0) * 100, 2) . '%');

        return Command::SUCCESS;
    }
}
