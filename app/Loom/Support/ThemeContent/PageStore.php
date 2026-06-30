<?php

namespace Loom\Support\ThemeContent;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PageStore extends ThemeFileStore
{
    public const PAGE_JSON_FILENAME = 'page.json';

    public const HOME_PAGE_SLUG = 'home';

    protected function subdirectory(): string
    {
        return 'pages';
    }

    protected function pageDirPath(string $slug, ?string $themeSlug = null): string
    {
        return $this->dirForTheme($themeSlug).'/'.$slug;
    }

    protected function filePath(string $slug, ?string $themeSlug = null): string
    {
        return $this->pageDirPath($slug, $themeSlug).'/'.self::PAGE_JSON_FILENAME;
    }

    protected function legacyFilePath(string $slug, ?string $themeSlug = null): string
    {
        return $this->dirForTheme($themeSlug).'/'.$slug.'.json';
    }

    public function urlExists(string $url, ?string $ignoreSlug = null, ?string $themeSlug = null): bool
    {
        $normalized = $this->normalizePageUrl($url);

        return $this->all($themeSlug)->contains(function (ThemeFileRecord $record) use ($normalized, $ignoreSlug) {
            if ($ignoreSlug !== null && $record->slug === $ignoreSlug) {
                return false;
            }

            $pageUrl = isset($record->url) && is_string($record->url)
                ? $this->normalizePageUrl($record->url)
                : '';

            return $pageUrl === $normalized;
        });
    }

    public function findByUrl(string $url, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $normalized = $this->normalizePageUrl($url);

        return $this->all($themeSlug)->first(function (ThemeFileRecord $record) use ($normalized) {
            $pageUrl = isset($record->url) && is_string($record->url)
                ? $this->normalizePageUrl($record->url)
                : '';

            return $pageUrl === $normalized;
        });
    }

    /**
     * @return Collection<int, ThemeFileRecord>
     */
    public function all(?string $themeSlug = null): Collection
    {
        $dir = $this->dirForTheme($themeSlug);

        if (! is_dir($dir)) {
            return collect();
        }

        $records = [];
        $seen = [];

        foreach (glob($dir.'/*/'.self::PAGE_JSON_FILENAME) ?: [] as $path) {
            $slug = basename(dirname($path));
            $record = $this->readFile($path, $slug);

            if ($record !== null) {
                $records[] = $record;
                $seen[$slug] = true;
            }
        }

        foreach (glob($dir.'/*.json') ?: [] as $path) {
            $slug = basename($path, '.json');

            if (isset($seen[$slug])) {
                continue;
            }

            $record = $this->readLegacyFile($path, $slug, $themeSlug);

            if ($record !== null) {
                $records[] = $record;
                $seen[$slug] = true;
            }
        }

        return collect($records)->sortByDesc(fn (ThemeFileRecord $record) => $record->updatedAt()->timestamp)->values();
    }

    public function find(string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $path = $this->filePath($slug, $themeSlug);

        if (file_exists($path)) {
            return $this->readFile($path, $slug);
        }

        $legacyPath = $this->legacyFilePath($slug, $themeSlug);

        if (file_exists($legacyPath)) {
            return $this->readLegacyFile($legacyPath, $slug, $themeSlug);
        }

        return null;
    }

    public function slugExists(string $slug, ?string $themeSlug = null, ?string $ignoreSlug = null): bool
    {
        if ($ignoreSlug !== null && $slug === $ignoreSlug) {
            return false;
        }

        return file_exists($this->filePath($slug, $themeSlug))
            || file_exists($this->legacyFilePath($slug, $themeSlug))
            || is_dir($this->pageDirPath($slug, $themeSlug));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $slug, array $data, ?string $themeSlug = null): ThemeFileRecord
    {
        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $existing = $this->findOrFail($slug, $themeSlug);

        $newSlug = $this->resolveSlugForUpdate($slug, $data, $themeSlug);

        if ($newSlug !== $slug && $this->slugExists($newSlug, $themeSlug)) {
            throw new InvalidArgumentException("A record with slug [{$newSlug}] already exists.");
        }

        $merged = array_merge($existing->toArray(), $data);
        $merged['slug'] = $newSlug;
        $merged['updated_at'] = now()->toIso8601String();

        $this->beforeUpdate($slug, $merged, $themeSlug);

        if ($newSlug !== $slug) {
            $this->removePageStorage($slug, $themeSlug);
        }

        $this->write($newSlug, $merged, $themeSlug);

        return $this->findOrFail($newSlug, $themeSlug);
    }

    public function delete(string $slug, ?string $themeSlug = null): void
    {
        $themeSlug = $themeSlug ?? $this->themes->activeSlug();

        if (! $this->slugExists($slug, $themeSlug, $slug)) {
            throw new InvalidArgumentException("Record [{$slug}] not found.");
        }

        $this->removePageStorage($slug, $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForCreate(array $data, string $themeSlug): string
    {
        $url = $this->normalizePageUrl($data['url'] ?? '');
        $slug = $this->slugFromUrl($url);

        if ($this->slugExists($slug, $themeSlug)) {
            if ($url === '') {
                throw new InvalidArgumentException('A homepage already exists for this theme.');
            }

            throw new InvalidArgumentException("A record with slug [{$slug}] already exists.");
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

        $url = $this->normalizePageUrl($data['url']);
        $slug = $this->slugFromUrl($url);

        if ($slug !== $currentSlug && $this->slugExists($slug, $themeSlug, $currentSlug)) {
            if ($url === '') {
                throw new InvalidArgumentException('A homepage already exists for this theme.');
            }

            throw new InvalidArgumentException("A record with slug [{$slug}] already exists.");
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data, string $themeSlug): void
    {
        if (isset($data['url']) && is_string($data['url'])) {
            $data['url'] = $this->normalizePageUrl($data['url']);
        }

        if (! isset($data['sections']) || ! is_array($data['sections'])) {
            $data['sections'] = [];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(string $currentSlug, array &$data, string $themeSlug): void
    {
        if (isset($data['url']) && is_string($data['url'])) {
            $data['url'] = $this->normalizePageUrl($data['url']);
        }

        if (! isset($data['sections']) || ! is_array($data['sections'])) {
            $data['sections'] = [];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $this->ensureDir($themeSlug);

        $pageDir = $this->pageDirPath($slug, $themeSlug);

        if (! is_dir($pageDir)) {
            File::makeDirectory($pageDir, 0755, true);
        }

        $payload = $this->normalizePagePayload($data);

        File::put(
            $this->filePath($slug, $themeSlug),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    protected function readLegacyFile(string $path, string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $record = $this->readFile($path, $slug);

        if ($record === null) {
            return null;
        }

        $this->write($slug, $record->toArray(), $themeSlug);
        File::delete($path);

        return $this->find($slug, $themeSlug);
    }

    protected function removePageStorage(string $slug, ?string $themeSlug = null): void
    {
        $pageDir = $this->pageDirPath($slug, $themeSlug);

        if (is_dir($pageDir)) {
            File::deleteDirectory($pageDir);
        }

        $legacyPath = $this->legacyFilePath($slug, $themeSlug);

        if (file_exists($legacyPath)) {
            File::delete($legacyPath);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePagePayload(array $data): array
    {
        $updatedAt = $data['updated_at'] ?? null;

        return [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'url' => isset($data['url']) && is_string($data['url'])
                ? $this->normalizePageUrl($data['url'])
                : '',
            'layout' => (string) ($data['layout'] ?? ''),
            'sections' => is_array($data['sections'] ?? null) ? $data['sections'] : [],
            'updated_at' => $updatedAt instanceof Carbon
                ? $updatedAt->toIso8601String()
                : (is_string($updatedAt) ? $updatedAt : now()->toIso8601String()),
        ];
    }

    protected function normalizePageUrl(mixed $url): string
    {
        if (! is_string($url)) {
            return '';
        }

        return strtolower(trim($url, '/'));
    }

    protected function slugFromUrl(string $normalizedUrl): string
    {
        if ($normalizedUrl === '') {
            return self::HOME_PAGE_SLUG;
        }

        $slug = Str::slug($normalizedUrl);

        if ($slug === '') {
            throw new InvalidArgumentException('Page URL is invalid.');
        }

        return $slug;
    }
}
