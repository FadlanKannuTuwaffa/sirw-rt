<?php

namespace App\Services\Assistant;

class ComplexMultiIntentHandler
{
    private array $intentDependencies = [
        'finance' => ['bills', 'payments'], // Finance butuh bills + payments
        'summary' => ['bills', 'agenda'], // Summary bisa gabung bills + agenda
    ];

    private array $intentPriority = [
        'bills' => 1,
        'payments' => 2,
        'agenda' => 3,
        'residents' => 4,
        'finance' => 5,
        'summary' => 6,
    ];

    public function detectIntents(string $message, array $scores): array
    {
        // Filter intent dengan confidence > 0.5
        $candidates = array_filter($scores, fn($score) => $score > 0.5);
        
        if (count($candidates) < 2) {
            return array_keys(array_slice($candidates, 0, 1));
        }

        // Sort by priority
        uksort($candidates, fn($a, $b) => 
            ($this->intentPriority[$a] ?? 99) <=> ($this->intentPriority[$b] ?? 99)
        );

        return array_keys($candidates);
    }

    public function resolveDependencies(array $intents): array
    {
        $resolved = [];
        
        foreach ($intents as $intent) {
            $deps = $this->intentDependencies[$intent] ?? [];
            
            foreach ($deps as $dep) {
                if (!in_array($dep, $resolved, true)) {
                    $resolved[] = $dep;
                }
            }
            
            if (!in_array($intent, $resolved, true)) {
                $resolved[] = $intent;
            }
        }

        return $resolved;
    }

    public function buildExecutionPlan(array $intents): array
    {
        $resolved = $this->resolveDependencies($intents);
        
        $plan = [];
        
        foreach ($resolved as $intent) {
            $plan[] = [
                'intent' => $intent,
                'dependencies' => $this->intentDependencies[$intent] ?? [],
                'order' => $this->intentPriority[$intent] ?? 99,
            ];
        }

        usort($plan, fn($a, $b) => $a['order'] <=> $b['order']);

        return $plan;
    }
}
