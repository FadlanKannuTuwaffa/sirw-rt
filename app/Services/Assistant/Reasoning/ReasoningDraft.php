<?php

namespace App\Services\Assistant\Reasoning;

/**
 * Value object that carries a drafted response along with verification metadata.
 */
class ReasoningDraft
{
    /**
     * @param  string        $intent
     * @param  string        $message
     * @param  array<int, array<string, mixed>>  $numerics
     * @param  array<int, array<string, mixed>>  $dates
     * @param  array<string, mixed>              $negation
     * @param  array<string, mixed>              $clarifications
     * @param  callable|null                     $repairCallback
     */
    public function __construct(
        public string $intent,
        public string $message,
        public array $numerics = [],
        public array $dates = [],
        public array $negation = [],
        public array $clarifications = [],
        public $repairCallback = null
    ) {
    }


    /**
     * Convenience helper so callers can rely on named arguments.
     */
    public static function make(
        string $intent,
        string $message,
        array $numerics = [],
        array $dates = [],
        array $negation = [],
        array $clarifications = [],
        $repairCallback = null
    ): self {
        return new self(
            intent: $intent,
            message: $message,
            numerics: $numerics,
            dates: $dates,
            negation: $negation,
            clarifications: $clarifications,
            repairCallback: $repairCallback
        );
    }

    public function with(array $override): self
    {
        return new self(
            intent: $override['intent'] ?? $this->intent,
            message: $override['message'] ?? $this->message,
            numerics: $override['numerics'] ?? $this->numerics,
            dates: $override['dates'] ?? $this->dates,
            negation: $override['negation'] ?? $this->negation,
            clarifications: $override['clarifications'] ?? $this->clarifications,
            repairCallback: $override['repairCallback'] ?? $this->repairCallback
        );
    }
}
