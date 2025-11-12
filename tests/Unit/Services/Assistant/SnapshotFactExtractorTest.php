<?php

namespace Tests\Unit\Services\Assistant;

use App\Models\AssistantLlmSnapshot;
use App\Services\Assistant\Support\LlmSnapshotFactExtractor;
use Tests\TestCase;

class SnapshotFactExtractorTest extends TestCase
{
    private LlmSnapshotFactExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new LlmSnapshotFactExtractor();
    }

    public function testExtractsBillFactsFromAmounts(): void
    {
        $snapshot = new AssistantLlmSnapshot([
            'intent' => 'bills',
            'content' => "- Iuran Kebersihan: Rp 30.000 (belum lunas)\n- Iuran Keamanan: Rp 25.000 (sudah lunas)",
            'metadata' => [
                'state' => [
                    'last_data' => [
                        ['id' => 11, 'title' => 'Iuran Kebersihan'],
                    ],
                ],
            ],
        ]);

        $facts = $this->extractor->extract($snapshot);

        $this->assertNotEmpty($facts);
        $this->assertTrue($this->containsFact($facts, 'bill', 'amount'));
        $this->assertTrue($this->containsFact($facts, 'bill', 'status'));
    }

    public function testExtractsResidentFacts(): void
    {
        $snapshot = new AssistantLlmSnapshot([
            'intent' => 'residents',
            'content' => 'Pak Budi - Ketua RT - 0812 1234 5678 tinggal di Blok A5',
            'metadata' => [
                'state' => [
                    'last_data' => [
                        ['id' => 42, 'name' => 'Pak Budi'],
                    ],
                ],
            ],
        ]);

        $facts = $this->extractor->extract($snapshot);

        $this->assertNotEmpty($facts);
        $this->assertTrue($this->containsFact($facts, 'resident', 'phone'));
        $this->assertTrue($this->containsFact($facts, 'resident', 'address'));
    }

    public function testSkipsNonStructuredIntent(): void
    {
        $snapshot = new AssistantLlmSnapshot([
            'intent' => 'smalltalk',
            'content' => 'Halo apa kabar?',
        ]);

        $facts = $this->extractor->extract($snapshot);

        $this->assertSame([], $facts);
    }
    /**
     * @param  array<int,array<string,mixed>>  $facts
     */
    private function containsFact(array $facts, string $entity, string $field): bool
    {
        foreach ($facts as $fact) {
            if (($fact['entity'] ?? null) === $entity && ($fact['field'] ?? null) === $field) {
                return true;
            }
        }

        return false;
    }
}
