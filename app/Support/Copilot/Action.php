<?php

namespace App\Support\Copilot;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Action implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $description,
        public readonly string $type = 'route',
        public readonly ?string $payload = null,
        public readonly array $meta = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            'payload' => $this->payload,
            'meta' => $this->meta,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
