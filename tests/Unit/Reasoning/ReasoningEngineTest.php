<?php

namespace Tests\Unit\Reasoning;

use App\Services\Assistant\Reasoning\ReasoningDraft;
use App\Services\Assistant\Reasoning\ReasoningEngine;
use App\Services\Assistant\Reasoning\ReasoningRules;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReasoningEngineTest extends TestCase
{
    public function test_difference_rule_triggers_violation_logger(): void
    {
        $engine = new ReasoningEngine('UTC');
        $logged = [];
        $engine->setViolationLogger(function (string $intent, array $violations) use (&$logged) {
            $logged[] = [$intent, $violations];
        });

        $message = $engine->run(function () {
            return ReasoningDraft::make(
                intent: 'summary',
                message: 'Total tidak sinkron',
                numerics: [
                    ReasoningRules::difference('diff', 10, 5, 2),
                ],
                clarifications: [
                    'numeric_difference_mismatch' => 'Datanya belum pas, bisa jelaskan ulang?',
                ]
            );
        });

        $this->assertSame('Datanya belum pas, bisa jelaskan ulang?', $message);
        $this->assertNotEmpty($logged);
        $this->assertSame('summary', $logged[0][0]);
        $this->assertSame('numeric_difference_mismatch', $logged[0][1][0]['code'] ?? null);
    }

    public function test_date_order_violation_returns_clarification(): void
    {
        $engine = new ReasoningEngine('UTC');

        $message = $engine->run(function () {
            return ReasoningDraft::make(
                intent: 'agenda',
                message: 'Agenda tidak valid',
                dates: [
                    ReasoningRules::dateOrder('agenda_dates', [Carbon::now(), Carbon::now()->subDay()]),
                ],
                clarifications: [
                    'date_order_invalid' => 'Tanggal agendanya kurang pas. Bisa sebutkan lagi?',
                ]
            );
        });

        $this->assertSame('Tanggal agendanya kurang pas. Bisa sebutkan lagi?', $message);
    }
}

