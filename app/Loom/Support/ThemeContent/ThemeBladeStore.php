<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

abstract class ThemeBladeStore extends ThemeFileStore
{
    protected function fileExtension(): string
    {
        return 'blade.php';
    }

    protected function filePath(string $slug, ?string $themeSlug = null): string
    {
        return $this->dirForTheme($themeSlug).'/'.$slug.'.'.$this->fileExtension();
    }

    protected function legacyJsonPath(string $slug, ?string $themeSlug = null): string
    {
        return $this->dirForTheme($themeSlug).'/'.$slug.'.json';
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
        $slugs = [];

        foreach (glob($dir.'/*.'.$this->fileExtension()) ?: [] as $path) {
            $slug = basename($path, '.'.$this->fileExtension());
            $record = $this->readFile($path, $slug, $themeSlug);

            if ($record !== null) {
                $records[] = $record;
                $slugs[$slug] = true;
            }
        }

        foreach (glob($dir.'/*.json') ?: [] as $path) {
            $slug = basename($path, '.json');

            if (isset($slugs[$slug])) {
                continue;
            }

            $record = $this->readLegacyJsonFile($path, $slug, $themeSlug);

            if ($record !== null) {
                $records[] = $record;
                $slugs[$slug] = true;
            }
        }

        return collect($records)->sortByDesc(fn (ThemeFileRecord $record) => $record->updatedAt()->timestamp)->values();
    }

    public function find(string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $path = $this->filePath($slug, $themeSlug);

        if (file_exists($path)) {
            return $this->readFile($path, $slug, $themeSlug);
        }

        $legacy = $this->legacyJsonPath($slug, $themeSlug);

        if (file_exists($legacy)) {
            return $this->readLegacyJsonFile($legacy, $slug, $themeSlug);
        }

        return null;
    }

    public function slugExists(string $slug, ?string $themeSlug = null, ?string $ignoreSlug = null): bool
    {
        if ($ignoreSlug !== null && $slug === $ignoreSlug) {
            return false;
        }

        return file_exists($this->filePath($slug, $themeSlug))
            || file_exists($this->legacyJsonPath($slug, $themeSlug));
    }

    public function delete(string $slug, ?string $themeSlug = null): void
    {
        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $path = $this->filePath($slug, $themeSlug);
        $legacy = $this->legacyJsonPath($slug, $themeSlug);

        if (! file_exists($path) && ! file_exists($legacy)) {
            throw new InvalidArgumentException("Record [{$slug}] not found.");
        }

        if (file_exists($path)) {
            File::delete($path);
        }

        if (file_exists($legacy)) {
            File::delete($legacy);
        }
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildMeta(array $data): array;

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    abstract protected function metaToRecordData(string $slug, array $meta, string $template): array;

    protected function readFile(string $path, string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $parsed = ThemeBladeDocument::parse((string) file_get_contents($path));
        $data = $this->metaToRecordData($slug, $parsed['meta'], $parsed['template']);
        $data['slug'] = $data['slug'] ?? $slug;

        if (isset($data['updated_at']) && is_string($data['updated_at'])) {
            $data['updated_at'] = \Carbon\Carbon::parse($data['updated_at']);
        } else {
            $data['updated_at'] = \Carbon\Carbon::createFromTimestamp(filemtime($path));
        }

        return new ThemeFileRecord($slug, $data);
    }

    protected function readLegacyJsonFile(string $path, string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return null;
        }

        $data['slug'] = $slug;

        $code = is_array($data['code'] ?? null) ? $data['code'] : ['template' => '', 'parameters' => []];
        $meta = $this->buildMeta(array_merge($data, [
            'code' => $code,
        ]));
        $template = (string) ($code['template'] ?? '');

        $this->write($slug, array_merge($data, ['code' => $code]), $themeSlug);
        File::delete($path);

        return $this->find($slug, $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $code = is_array($data['code'] ?? null) ? $data['code'] : ['template' => '', 'parameters' => []];
        $template = (string) ($code['template'] ?? '');
        $meta = $this->buildMeta($data);
        $meta['updated_at'] = ($data['updated_at'] ?? null) instanceof \Carbon\Carbon
            ? $data['updated_at']->toIso8601String()
            : (is_string($data['updated_at'] ?? null) ? $data['updated_at'] : now()->toIso8601String());

        File::put(
            $this->filePath($slug, $themeSlug),
            ThemeBladeDocument::compose($meta, $template)
        );
    }
}
