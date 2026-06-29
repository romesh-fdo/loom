<?php

namespace Loom\Support\ThemeContent;

use Carbon\Carbon;

class ThemeFileRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $slug,
        protected array $data,
    ) {}

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function updatedAt(): Carbon
    {
        $value = $this->data['updated_at'] ?? null;

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return Carbon::now();
    }
}
