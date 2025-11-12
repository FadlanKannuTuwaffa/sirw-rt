<?php

namespace App\Support\Copilot;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Insight implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $severity = 'info',
        public readonly array $tags = [],
        public readonly ?array $action = null,
        public readonly ?string $icon = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity,
            'tags' => $this->tags,
            'action' => $this->action,
            'icon' => $this->icon,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
