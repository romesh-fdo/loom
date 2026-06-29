<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Loom\Support\ThemeManager;

abstract class ThemeFileStore
{
    public function __construct(
        protected ThemeManager $themes,
    ) {}

    abstract protected function subdirectory(): string;

    protected function dirForTheme(?string $themeSlug = null): string
    {
        $slug = $themeSlug ?? $this->themes->activeSlug();

        return $this->themes->themesPath().'/'.$slug.'/'.$this->subdirectory();
    }

    protected function filePath(string $slug, ?string $themeSlug = null): string
    {
        return $this->dirForTheme($themeSlug).'/'.$slug.'.json';
    }

    protected function ensureDir(?string $themeSlug = null): void
    {
        $dir = $this->dirForTheme($themeSlug);

        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
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

        foreach (glob($dir.'/*.json') ?: [] as $path) {
            $slug = basename($path, '.json');
            $record = $this->readFile($path, $slug);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        return collect($records)->sortByDesc(fn (ThemeFileRecord $record) => $record->updatedAt()->timestamp)->values();
    }

    public function search(?string $query, ?string $themeSlug = null): Collection
    {
        $items = $this->all($themeSlug);

        if ($query === null || trim($query) === '') {
            return $items;
        }

        $needle = strtolower(trim($query));

        return $items->filter(function (ThemeFileRecord $record) use ($needle) {
            $name = strtolower((string) ($record->name ?? ''));

            return str_contains($name, $needle);
        })->values();
    }

    public function paginate(?string $query, int $perPage, int $page, ?string $themeSlug = null): LengthAwarePaginator
    {
        $items = $this->search($query, $themeSlug);
        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function find(string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $path = $this->filePath($slug, $themeSlug);

        if (! file_exists($path)) {
            return null;
        }

        return $this->readFile($path, $slug);
    }

    public function findOrFail(string $slug, ?string $themeSlug = null): ThemeFileRecord
    {
        $record = $this->find($slug, $themeSlug);

        if ($record === null) {
            throw new InvalidArgumentException("Record [{$slug}] not found.");
        }

        return $record;
    }

    public function slugExists(string $slug, ?string $themeSlug = null, ?string $ignoreSlug = null): bool
    {
        if ($ignoreSlug !== null && $slug === $ignoreSlug) {
            return false;
        }

        return file_exists($this->filePath($slug, $themeSlug));
    }

  /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?string $themeSlug = null): ThemeFileRecord
    {
        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $slug = $this->resolveSlugForCreate($data, $themeSlug);

        if ($this->slugExists($slug, $themeSlug)) {
            throw new InvalidArgumentException("A record with slug [{$slug}] already exists.");
        }

        $data['slug'] = $slug;
        $data['updated_at'] = now()->toIso8601String();

        $this->beforeCreate($data, $themeSlug);
        $this->ensureDir($themeSlug);
        $this->write($slug, $data, $themeSlug);

        return $this->findOrFail($slug, $themeSlug);
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
            File::delete($this->filePath($slug, $themeSlug));
        }

        $this->write($newSlug, $merged, $themeSlug);

        return $this->findOrFail($newSlug, $themeSlug);
    }

    public function delete(string $slug, ?string $themeSlug = null): void
    {
        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $path = $this->filePath($slug, $themeSlug);

        if (! file_exists($path)) {
            throw new InvalidArgumentException("Record [{$slug}] not found.");
        }

        File::delete($path);
    }

    protected function generateSlug(string $name, ?string $themeSlug = null): string
    {
        $base = Str::slug(trim($name));

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $themeSlug)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForCreate(array $data, string $themeSlug): string
    {
        if (isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== '') {
            return Str::slug($data['slug']);
        }

        return $this->generateSlug((string) ($data['name'] ?? 'item'), $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForUpdate(string $currentSlug, array $data, string $themeSlug): string
    {
        return $currentSlug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data, string $themeSlug): void
    {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(string $currentSlug, array &$data, string $themeSlug): void
    {
        //
    }

    protected function readFile(string $path, string $slug): ?ThemeFileRecord
    {
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return null;
        }

        $data['slug'] = $data['slug'] ?? $slug;

        if (isset($data['updated_at']) && is_string($data['updated_at'])) {
            $data['updated_at'] = \Carbon\Carbon::parse($data['updated_at']);
        } elseif (! isset($data['updated_at'])) {
            $data['updated_at'] = \Carbon\Carbon::createFromTimestamp(filemtime($path));
        }

        return new ThemeFileRecord($slug, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $payload = $data;
        $updatedAt = $payload['updated_at'] ?? null;
        $payload['updated_at'] = $updatedAt instanceof \Carbon\Carbon
            ? $updatedAt->toIso8601String()
            : (is_string($updatedAt) ? $updatedAt : now()->toIso8601String());

        File::put(
            $this->filePath($slug, $themeSlug),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }
}
