<?php

/**
 * RUNNER STRESS TEST
 * Jalankan: php tests/run_stress_test.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Assistant\DummyClient;

$tests = require __DIR__ . '/datasets/dummy_client_stress_cases.php';
$client = app(DummyClient::class);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          DUMMY CLIENT STRESS TEST - BEFORE IMPROVE         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$totalTests = 0;
$passedTests = 0;
$results = [];

foreach ($tests as $category => $cases) {
    echo "📂 KATEGORI: " . strtoupper(str_replace('_', ' ', $category)) . "\n";
    echo str_repeat('─', 60) . "\n";
    
    foreach ($cases as $index => $case) {
        $totalTests++;
        $input = $case['input'];
        $expect = $case['expect'];
        
        // Simulasi chat
        $messages = [['role' => 'user', 'content' => $input]];
        
        try {
            $response = $client->chat($messages);
            $output = $response['content'] ?? 'No response';
            
            // Evaluasi sederhana: cek apakah response relevan
            $isRelevant = evaluateResponse($input, $output, $expect);
            
            if ($isRelevant) {
                $passedTests++;
                $status = "✅ PASS";
            } else {
                $status = "❌ FAIL";
            }
            
            echo sprintf(
                "%d. %s\n   Input: %s\n   Expect: %s\n   Output: %s\n\n",
                $index + 1,
                $status,
                $input,
                $expect,
                substr($output, 0, 100) . (strlen($output) > 100 ? '...' : '')
            );
            
            $results[$category][] = [
                'input' => $input,
                'expect' => $expect,
                'output' => $output,
                'passed' => $isRelevant
            ];
            
        } catch (Exception $e) {
            echo sprintf(
                "%d. ⚠️  ERROR\n   Input: %s\n   Error: %s\n\n",
                $index + 1,
                $input,
                $e->getMessage()
            );
        }
    }
    
    echo "\n";
}

$score = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                      HASIL AKHIR                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo sprintf("Total Test: %d\n", $totalTests);
echo sprintf("Passed: %d\n", $passedTests);
echo sprintf("Failed: %d\n", $totalTests - $passedTests);
echo sprintf("Score: %s%% 🧠\n\n", $score);

// Simpan hasil
file_put_contents(
    __DIR__ . '/stress_test_results_before.json',
    json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'total' => $totalTests,
        'passed' => $passedTests,
        'score' => $score,
        'details' => $results
    ], JSON_PRETTY_PRINT)
);

echo "📊 Hasil disimpan di: tests/stress_test_results_before.json\n";

// Helper function
function evaluateResponse($input, $output, $expect) {
    // Evaluasi sederhana: cek apakah output bukan fallback generic
    $genericResponses = [
        'Hmm, aku belum bisa jawab itu',
        'Hai! Ada yang bisa kubantu?',
        'Aku belum mengerti'
    ];
    
    foreach ($genericResponses as $generic) {
        if (stripos($output, $generic) !== false) {
            return false;
        }
    }
    
    // Cek apakah ada data relevan di output
    $keywords = ['tagihan', 'agenda', 'pembayaran', 'warga', 'Rp', 'Total'];
    foreach ($keywords as $keyword) {
        if (stripos($output, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}
