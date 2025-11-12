<?php

namespace Tests\Unit\Services\Assistant;

use App\Services\Assistant\QueryClassifier;
use Tests\TestCase;

class QueryClassifierTest extends TestCase
{
    private QueryClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new QueryClassifier();
    }

    public function test_classifies_small_talk()
    {
        $result = $this->classifier->classify('Halo');
        $this->assertEquals('small_talk', $result['type']);
        $this->assertGreaterThan(0.9, $result['confidence']);

        $result = $this->classifier->classify('Terima kasih');
        $this->assertEquals('small_talk', $result['type']);
    }

    public function test_classifies_simple_query()
    {
        $result = $this->classifier->classify('Tagihanku berapa?');
        $this->assertEquals('simple', $result['type']);
        $this->assertEquals('tagihan', $result['category']);
        $this->assertGreaterThan(0.7, $result['confidence']);

        $result = $this->classifier->classify('Agenda minggu ini apa?');
        $this->assertEquals('simple', $result['type']);
        $this->assertEquals('agenda', $result['category']);
    }

    public function test_classifies_complex_query()
    {
        $result = $this->classifier->classify('Kenapa iuran naik?');
        $this->assertEquals('complex', $result['type']);

        $result = $this->classifier->classify('Jelaskan perbedaan iuran sampah dan keamanan');
        $this->assertEquals('complex', $result['type']);

        $result = $this->classifier->classify('Bagaimana jika saya tidak bayar?');
        $this->assertEquals('complex', $result['type']);
    }

    public function test_short_messages_are_small_talk()
    {
        $result = $this->classifier->classify('Hi');
        $this->assertEquals('small_talk', $result['type']);

        $result = $this->classifier->classify('Ok');
        $this->assertEquals('small_talk', $result['type']);
    }

    public function test_confidence_scores()
    {
        $result = $this->classifier->classify('Tagihanku bulan ini berapa?');
        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(1, $result['confidence']);
    }

    public function test_detects_complex_structure()
    {
        $result = $this->classifier->classify('Jika saya bayar sekarang atau besok, apakah ada diskon?');
        $this->assertEquals('complex', $result['type']);
    }

    public function test_multiple_keywords_increase_confidence()
    {
        $result1 = $this->classifier->classify('tagihan');
        $result2 = $this->classifier->classify('tagihan iuran bayar');
        
        if ($result1['type'] === 'simple' && $result2['type'] === 'simple') {
            $this->assertGreaterThanOrEqual($result1['confidence'], $result2['confidence']);
        }
    }
}
