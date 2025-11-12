<?php

namespace App\Support\Copilot;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class TimelineEntry implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $icon,
        public readonly string $title,
        public readonly string $description,
        public readonly string $time,
        public readonly array $tags = [],
        public readonly ?int $sortValue = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'title' => $this->title,
            'description' => $this->description,
            'time' => $this->time,
            'tags' => $this->tags,
            'sort' => $this->sortValue,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
