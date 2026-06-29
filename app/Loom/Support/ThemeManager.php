<?php

namespace Loom\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class ThemeManager
{
    public const DEFAULT_SLUG = 'default';

    public const PREVIEW_BASENAME = 'preview';

    public function themesPath(): string
    {
        return base_path('theme');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        $themesPath = $this->themesPath();

        if (! is_dir($themesPath)) {
            return collect();
        }

        $themes = [];

        foreach (glob($themesPath.'/*/theme.json') ?: [] as $manifestPath) {
            $data = json_decode((string) file_get_contents($manifestPath), true);

            if (is_array($data)) {
                $themes[] = $this->enrich($data);
            }
        }

        return collect($themes)->sortBy('name')->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        $manifest = $this->readManifest($slug);

        return $manifest !== null ? $this->enrich($manifest) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function readManifest(string $slug): ?array
    {
        $manifestPath = $this->themesPath().'/'.$slug.'/theme.json';

        if (! file_exists($manifestPath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($manifestPath), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    public function enrich(array $theme): array
    {
        $theme['preview_url'] = $this->previewUrl($theme);

        return $theme;
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public function previewUrl(array $theme): ?string
    {
        $slug = $theme['slug'] ?? null;
        $image = $theme['image'] ?? null;

        if (! is_string($slug) || ! is_string($image) || $image === '') {
            return null;
        }

        $path = $this->themesPath().'/'.$slug.'/'.$image;

        if (! file_exists($path)) {
            return null;
        }

        return url('theme/'.$slug.'/'.$image);
    }

    public function activeSlug(): string
    {
        return (string) config('loom.active_theme', self::DEFAULT_SLUG);
    }

    public function assetsPath(string $slug): string
    {
        return 'theme/'.$slug.'/assets';
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, string $slug, ?UploadedFile $image = null): array
    {
        $slug = strtolower(trim($slug));

        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new InvalidArgumentException('Theme slug may only contain lowercase letters, numbers, and hyphens.');
        }

        if ($this->readManifest($slug) !== null) {
            throw new InvalidArgumentException("Theme [{$slug}] already exists.");
        }

        $defaultAssets = $this->themesPath().'/'.self::DEFAULT_SLUG.'/assets';

        if (! is_dir($defaultAssets)) {
            throw new InvalidArgumentException('Default theme assets folder not found. Run php artisan loom:theme-migrate first.');
        }

        $themeDir = $this->themesPath().'/'.$slug;
        $assetsDir = $themeDir.'/assets';

        File::makeDirectory($assetsDir, 0755, true);
        File::copyDirectory($defaultAssets, $assetsDir);

        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'description' => '',
            'version' => '1.0.0',
            'author' => 'Loom',
            'created_at' => now()->toIso8601String(),
        ];

        if ($image !== null) {
            $manifest['image'] = $this->storePreviewImage($slug, $image);
        }

        $this->writeManifest($slug, $manifest);

        return $this->enrich($manifest);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateImage(string $slug, UploadedFile $image): array
    {
        $manifest = $this->readManifest($slug);

        if ($manifest === null) {
            throw new InvalidArgumentException("Theme [{$slug}] not found.");
        }

        $manifest['image'] = $this->storePreviewImage($slug, $image);
        $this->writeManifest($slug, $manifest);

        return $this->enrich($manifest);
    }

    public function storePreviewImage(string $slug, UploadedFile $image): string
    {
        $themeDir = $this->themesPath().'/'.$slug;

        if (! is_dir($themeDir)) {
            throw new InvalidArgumentException("Theme [{$slug}] not found.");
        }

        foreach (glob($themeDir.'/'.self::PREVIEW_BASENAME.'.*') ?: [] as $existing) {
            File::delete($existing);
        }

        $extension = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg');
        $filename = self::PREVIEW_BASENAME.'.'.$extension;

        $image->move($themeDir, $filename);

        return $filename;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function writeManifest(string $slug, array $manifest): void
    {
        File::put(
            $this->themesPath().'/'.$slug.'/theme.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function activate(string $slug): void
    {
        if ($this->readManifest($slug) === null) {
            throw new InvalidArgumentException("Theme [{$slug}] not found.");
        }

        app(EnvWriter::class)->set('LOOM_ACTIVE_THEME', $slug);

        $assetsPath = $this->assetsPath($slug);

        config(['loom.active_theme' => $slug]);
        config(['loom.assets.public_path' => $assetsPath]);
        config(['filesystems.disks.assets.root' => base_path($assetsPath)]);
        config([
            'filesystems.disks.assets.url' => rtrim((string) config('app.url'), '/').'/'.$assetsPath,
        ]);
    }

    public function slugExists(string $slug): bool
    {
        return $this->readManifest($slug) !== null;
    }
}
