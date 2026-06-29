<?php

namespace Loom\Support\ThemeContent;

use InvalidArgumentException;

class SegmentStore extends ThemeBladeStore
{
    protected function subdirectory(): string
    {
        return 'segments';
    }

    public function slotExists(string $slot, ?string $ignoreSlug = null, ?string $themeSlug = null): bool
    {
        return $this->all($themeSlug)->contains(function (ThemeFileRecord $record) use ($slot, $ignoreSlug) {
            if ($ignoreSlug !== null && $record->slug === $ignoreSlug) {
                return false;
            }

            return ($record->slot ?? '') === $slot;
        });
    }

    public function findBySlot(string $slot, ?string $themeSlug = null): ?ThemeFileRecord
    {
        return $this->all($themeSlug)->first(fn (ThemeFileRecord $record) => ($record->slot ?? '') === $slot);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data, string $themeSlug): void
    {
        $slot = $data['slot'] ?? null;

        if (! is_string($slot) || $slot === '') {
            throw new InvalidArgumentException('Segment slot is required.');
        }

        if ($this->slotExists($slot, null, $themeSlug)) {
            throw new InvalidArgumentException("A segment for slot [{$slot}] already exists.");
        }

        $data['enabled'] = array_key_exists('enabled', $data) ? (bool) $data['enabled'] : true;
        $data['values'] = is_array($data['values'] ?? null) ? $data['values'] : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(string $currentSlug, array &$data, string $themeSlug): void
    {
        if (isset($data['slot']) && is_string($data['slot']) && $data['slot'] !== '') {
            if ($this->slotExists($data['slot'], $currentSlug, $themeSlug)) {
                throw new InvalidArgumentException("A segment for slot [{$data['slot']}] already exists.");
            }
        }

        if (array_key_exists('enabled', $data)) {
            $data['enabled'] = (bool) $data['enabled'];
        }

        if (isset($data['values']) && ! is_array($data['values'])) {
            $data['values'] = [];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildMeta(array $data): array
    {
        $code = is_array($data['code'] ?? null) ? $data['code'] : [];

        return [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'slot' => (string) ($data['slot'] ?? ''),
            'enabled' => (bool) ($data['enabled'] ?? true),
            'parameters' => is_array($code['parameters'] ?? null) ? $code['parameters'] : [],
            'values' => is_array($data['values'] ?? null) ? $data['values'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function metaToRecordData(string $slug, array $meta, string $template): array
    {
        $parameters = is_array($meta['parameters'] ?? null) ? $meta['parameters'] : [];

        return [
            'name' => $meta['name'] ?? $slug,
            'slug' => $meta['slug'] ?? $slug,
            'slot' => $meta['slot'] ?? '',
            'enabled' => (bool) ($meta['enabled'] ?? true),
            'code' => [
                'template' => $template,
                'parameters' => $parameters,
            ],
            'values' => is_array($meta['values'] ?? null) ? $meta['values'] : [],
            'updated_at' => $meta['updated_at'] ?? null,
        ];
    }
}
