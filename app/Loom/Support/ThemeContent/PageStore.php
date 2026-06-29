<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Support\Str;
use InvalidArgumentException;

class PageStore extends ThemeFileStore
{
    protected function subdirectory(): string
    {
        return 'pages';
    }

    public function urlExists(string $url, ?string $ignoreSlug = null, ?string $themeSlug = null): bool
    {
        $slug = Str::slug($url);

        return $this->slugExists($slug, $themeSlug, $ignoreSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForCreate(array $data, string $themeSlug): string
    {
        $url = isset($data['url']) && is_string($data['url'])
            ? strtolower(trim($data['url'], '/'))
            : '';

        if ($url === '') {
            throw new InvalidArgumentException('Page URL is required.');
        }

        $slug = Str::slug($url);

        if ($slug === '') {
            throw new InvalidArgumentException('Page URL is invalid.');
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForUpdate(string $currentSlug, array $data, string $themeSlug): string
    {
        if (! isset($data['url']) || ! is_string($data['url'])) {
            return $currentSlug;
        }

        $url = strtolower(trim($data['url'], '/'));
        $slug = Str::slug($url);

        if ($slug === '') {
            throw new InvalidArgumentException('Page URL is invalid.');
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data, string $themeSlug): void
    {
        if (isset($data['url']) && is_string($data['url'])) {
            $data['url'] = strtolower(trim($data['url'], '/'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(string $currentSlug, array &$data, string $themeSlug): void
    {
        if (isset($data['url']) && is_string($data['url'])) {
            $data['url'] = strtolower(trim($data['url'], '/'));
        }
    }
}
