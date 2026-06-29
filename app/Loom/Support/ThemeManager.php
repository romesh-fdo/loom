<?php

namespace Loom\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ThemeManager
{
    public const DEFAULT_SLUG = 'default';

    public const PREVIEW_BASENAME = 'preview';

    /**
     * @return array<string, array{label: string}>
     */
    public static function defaultSegmentSlots(): array
    {
        return [
            'header' => ['label' => 'Header / Navbar'],
            'topbar' => ['label' => 'Top bar'],
            'footer' => ['label' => 'Footer'],
            'search_overlay' => ['label' => 'Search overlay'],
            'scroll_to_top' => ['label' => 'Scroll to top'],
            'body_end' => ['label' => 'Before </body>'],
        ];
    }

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
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function create(array $fields, ?UploadedFile $image = null): array
    {
        $name = (string) $fields['name'];
        $slug = $this->generateSlug($name);

        $themeDir = $this->themesPath().'/'.$slug;

        File::makeDirectory($themeDir.'/assets', 0755, true);
        $this->ensureContentDirectories($slug);

        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'description' => $fields['description'] ?? '',
            'version' => $fields['version'] ?? '1.0.0',
            'author' => $fields['author'] ?? '',
            'created_at' => now()->toIso8601String(),
            'segment_slots' => self::defaultSegmentSlots(),
        ];

        if ($image !== null) {
            $manifest['image'] = $this->storePreviewImage($slug, $image);
        }

        $this->writeManifest($slug, $manifest);
        $this->copyContentFromTheme(self::DEFAULT_SLUG, $slug);

        return $this->enrich($manifest);
    }

    public function ensureContentDirectories(string $slug): void
    {
        $themeDir = $this->themesPath().'/'.$slug;

        foreach (['blocks', 'pages', 'segments'] as $subdir) {
            $path = $themeDir.'/'.$subdir;

            if (! is_dir($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    public function copyContentFromTheme(string $sourceSlug, string $targetSlug): void
    {
        if ($sourceSlug === $targetSlug) {
            return;
        }

        $sourceDir = $this->themesPath().'/'.$sourceSlug;
        $targetDir = $this->themesPath().'/'.$targetSlug;

        if (! is_dir($sourceDir)) {
            return;
        }

        $this->ensureContentDirectories($targetSlug);

        foreach (['blocks', 'pages', 'segments'] as $subdir) {
            $from = $sourceDir.'/'.$subdir;
            $to = $targetDir.'/'.$subdir;

            if (! is_dir($from)) {
                continue;
            }

            $patterns = $subdir === 'pages'
                ? ['*.json']
                : ['*.blade.php', '*.json'];

            foreach ($patterns as $pattern) {
                foreach (glob($from.'/'.$pattern) ?: [] as $file) {
                    File::copy($file, $to.'/'.basename($file));
                }
            }
        }
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

    /**
     * @return array<string, mixed>
     */
    public function updateInfo(string $slug, array $fields): array
    {
        $manifest = $this->readManifest($slug);

        if ($manifest === null) {
            throw new InvalidArgumentException("Theme [{$slug}] not found.");
        }

        $manifest['name'] = $fields['name'];
        $manifest['description'] = $fields['description'] ?? '';
        $manifest['version'] = $fields['version'] ?? ($manifest['version'] ?? '1.0.0');
        $manifest['author'] = $fields['author'] ?? '';
        $manifest['slug'] = $slug;

        if (! isset($manifest['segment_slots'])) {
            $manifest['segment_slots'] = self::defaultSegmentSlots();
        }

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

    public function delete(string $slug): void
    {
        if ($slug === $this->activeSlug()) {
            throw new InvalidArgumentException('Cannot delete the active theme.');
        }

        if ($this->readManifest($slug) === null) {
            throw new InvalidArgumentException("Theme [{$slug}] not found.");
        }

        File::deleteDirectory($this->themesPath().'/'.$slug);
    }

    public function slugExists(string $slug): bool
    {
        return $this->readManifest($slug) !== null;
    }

    public function generateSlug(string $name): string
    {
        $base = Str::slug(trim($name));

        if ($base === '') {
            $base = 'theme';
        }

        if ($base === self::DEFAULT_SLUG) {
            $base = 'theme';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->readManifest($slug) !== null) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
