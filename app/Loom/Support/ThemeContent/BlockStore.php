<?php

namespace Loom\Support\ThemeContent;

class BlockStore extends ThemeBladeStore
{
    protected function subdirectory(): string
    {
        return 'blocks';
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
            'parameters' => is_array($code['parameters'] ?? null) ? $code['parameters'] : [],
            'config' => $data['config'] ?? null,
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
            'code' => [
                'template' => $template,
                'parameters' => $parameters,
            ],
            'config' => $meta['config'] ?? null,
            'updated_at' => $meta['updated_at'] ?? null,
        ];
    }
}
