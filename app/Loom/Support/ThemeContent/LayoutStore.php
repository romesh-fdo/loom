<?php

namespace Loom\Support\ThemeContent;

class LayoutStore extends ThemeBladeStore
{
    protected function subdirectory(): string
    {
        return 'layouts';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildMeta(array $data): array
    {
        return [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function metaToRecordData(string $slug, array $meta, string $template): array
    {
        return [
            'name' => $meta['name'] ?? $slug,
            'slug' => $meta['slug'] ?? $slug,
            'code' => $template,
            'updated_at' => $meta['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $template = $this->resolveTemplate($data);

        parent::write($slug, array_merge($data, [
            'code' => ['template' => $template, 'parameters' => []],
        ]), $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveTemplate(array $data): string
    {
        if (is_string($data['code'] ?? null)) {
            return $data['code'];
        }

        if (is_array($data['code'] ?? null)) {
            return (string) ($data['code']['template'] ?? '');
        }

        return '';
    }
}
