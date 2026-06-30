<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SegmentStore extends ThemeBladeStore
{
    protected function subdirectory(): string
    {
        return 'segments';
    }

    protected function filePath(string $slug, ?string $themeSlug = null): string
    {
        $slug = SegmentPath::normalize($slug);
        SegmentPath::validate($slug);

        return $this->dirForTheme($themeSlug).'/'.$slug.'.'.$this->fileExtension();
    }

    protected function legacyJsonPath(string $slug, ?string $themeSlug = null): string
    {
        $slug = SegmentPath::normalize($slug);
        SegmentPath::validate($slug);

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
        $extension = '.'.$this->fileExtension();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (str_ends_with($path, $extension)) {
                $relative = substr($path, strlen($dir) + 1);
                $slug = SegmentPath::normalize(substr($relative, 0, -strlen($extension)));
                $record = $this->readFile($path, $slug, $themeSlug);

                if ($record !== null) {
                    $records[] = $record;
                    $slugs[$slug] = true;
                }

                continue;
            }

            if (str_ends_with($path, '.json')) {
                $relative = substr($path, strlen($dir) + 1);
                $slug = SegmentPath::normalize(substr($relative, 0, -5));

                if (isset($slugs[$slug])) {
                    continue;
                }

                $record = $this->readLegacyJsonFile($path, $slug, $themeSlug);

                if ($record !== null) {
                    $records[] = $record;
                    $slugs[$slug] = true;
                }
            }
        }

        return collect($records)->sortByDesc(fn (ThemeFileRecord $record) => $record->updatedAt()->timestamp)->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(?string $themeSlug = null): array
    {
        $dir = $this->dirForTheme($themeSlug);
        $root = ['name' => 'segments', 'path' => '', 'type' => 'folder', 'children' => []];

        if (! is_dir($dir)) {
            return [$root];
        }

        $this->buildTreeNode($dir, '', $root);

        usort($root['children'], fn (array $a, array $b) => $this->compareTreeNodes($a, $b));

        return [$root];
    }

    public function createFolder(string $path, ?string $themeSlug = null): void
    {
        $path = SegmentPath::normalize($path);
        SegmentPath::validate($path);

        if ($path === '') {
            throw new InvalidArgumentException('Folder path is required.');
        }

        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $fullPath = $this->dirForTheme($themeSlug).'/'.$path;

        if (is_dir($fullPath)) {
            throw new InvalidArgumentException("Folder [{$path}] already exists.");
        }

        if (file_exists($fullPath)) {
            throw new InvalidArgumentException("A file exists at [{$path}].");
        }

        File::makeDirectory($fullPath, 0755, true);
    }

    public function renameFolder(string $from, string $to, ?string $themeSlug = null): void
    {
        $from = SegmentPath::normalize($from);
        $to = SegmentPath::normalize($to);
        SegmentPath::validate($from);
        SegmentPath::validate($to);

        if ($from === '' || $to === '') {
            throw new InvalidArgumentException('Folder paths are required.');
        }

        if ($from === $to) {
            return;
        }

        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $baseDir = $this->dirForTheme($themeSlug);
        $fromPath = $baseDir.'/'.$from;
        $toPath = $baseDir.'/'.$to;

        if (! is_dir($fromPath)) {
            throw new InvalidArgumentException("Folder [{$from}] not found.");
        }

        if (is_dir($toPath) || file_exists($toPath)) {
            throw new InvalidArgumentException("Folder [{$to}] already exists.");
        }

        $prefix = $from.'/';
        $records = $this->all($themeSlug)->filter(
            fn (ThemeFileRecord $record) => str_starts_with($record->slug, $prefix)
        );

        File::moveDirectory($fromPath, $toPath);

        foreach ($records as $record) {
            $newSlug = $to.'/'.substr($record->slug, strlen($prefix));
            $data = $record->toArray();
            $data['slug'] = $newSlug;
            $this->write($newSlug, $data, $themeSlug);
        }
    }

    public function moveSegment(string $from, string $to, ?string $themeSlug = null, ?string $name = null): ThemeFileRecord
    {
        $from = SegmentPath::normalize($from);
        $to = SegmentPath::normalize($to);
        SegmentPath::validate($from);
        SegmentPath::validate($to);

        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $name = is_string($name) ? trim($name) : null;

        if ($name === '') {
            $name = null;
        }

        if ($from === $to) {
            if ($name === null) {
                return $this->findOrFail($from, $themeSlug);
            }

            $record = $this->findOrFail($from, $themeSlug);
            $data = $record->toArray();
            $data['name'] = $name;
            $data['updated_at'] = now()->toIso8601String();
            $this->write($from, $data, $themeSlug);

            return $this->findOrFail($from, $themeSlug);
        }

        if ($this->find($from, $themeSlug) === null) {
            throw new InvalidArgumentException("Segment [{$from}] not found.");
        }

        if ($this->slugExists($to, $themeSlug)) {
            throw new InvalidArgumentException("A segment [{$to}] already exists.");
        }

        $record = $this->findOrFail($from, $themeSlug);
        $oldPath = $this->filePath($from, $themeSlug);
        $oldLegacy = $this->legacyJsonPath($from, $themeSlug);

        $parentDir = dirname($this->filePath($to, $themeSlug));

        if (! is_dir($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }

        $data = $record->toArray();
        $data['slug'] = $to;
        $data['updated_at'] = now()->toIso8601String();

        if ($name !== null) {
            $data['name'] = $name;
        } elseif (SegmentPath::dirname($from) === SegmentPath::dirname($to)) {
            $data['name'] = SegmentPath::basename($to);
        }

        $this->write($to, $data, $themeSlug);

        if (file_exists($oldPath)) {
            File::delete($oldPath);
        }

        if (file_exists($oldLegacy)) {
            File::delete($oldLegacy);
        }

        return $this->findOrFail($to, $themeSlug);
    }

    public function deleteFolder(string $path, ?string $themeSlug = null): void
    {
        $path = SegmentPath::normalize($path);
        SegmentPath::validate($path);

        if ($path === '') {
            throw new InvalidArgumentException('Cannot delete the root folder.');
        }

        $themeSlug = $themeSlug ?? $this->themes->activeSlug();
        $fullPath = $this->dirForTheme($themeSlug).'/'.$path;

        if (! is_dir($fullPath)) {
            throw new InvalidArgumentException("Folder [{$path}] not found.");
        }

        File::deleteDirectory($fullPath);
    }

    public function findBySlot(string $slot, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $themeSlug ??= $this->themes->activeSlug();

        foreach ($this->all($themeSlug) as $record) {
            if ($this->legacySlotForSegment($record->slug, $themeSlug) === $slot) {
                return $record;
            }
        }

        return null;
    }

    protected function legacySlotForSegment(string $slug, ?string $themeSlug): string
    {
        $path = $this->filePath($slug, $themeSlug);

        if (! file_exists($path)) {
            return '';
        }

        $parsed = ThemeBladeDocument::parse((string) file_get_contents($path));

        return (string) ($parsed['meta']['slot'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeCreate(array &$data, string $themeSlug): void
    {
        $data['enabled'] = array_key_exists('enabled', $data) ? (bool) $data['enabled'] : true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function beforeUpdate(string $currentSlug, array &$data, string $themeSlug): void
    {
        if (array_key_exists('enabled', $data)) {
            $data['enabled'] = (bool) $data['enabled'];
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
            'enabled' => (bool) ($data['enabled'] ?? true),
            'parameters' => is_array($code['parameters'] ?? null) ? $code['parameters'] : [],
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
            'name' => $meta['name'] ?? SegmentPath::basename($slug),
            'slug' => $meta['slug'] ?? $slug,
            'enabled' => (bool) ($meta['enabled'] ?? true),
            'code' => [
                'template' => $template,
                'parameters' => $parameters,
            ],
            'updated_at' => $meta['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForCreate(array $data, string $themeSlug): string
    {
        $folder = isset($data['folder']) && is_string($data['folder'])
            ? SegmentPath::normalize($data['folder'])
            : '';

        if ($folder !== '') {
            SegmentPath::validate($folder);
        }

        if (isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== '') {
            $filename = SegmentPath::slugifySegment($data['slug']);
        } else {
            $filename = SegmentPath::slugifySegment((string) ($data['name'] ?? 'segment'));
        }

        $slug = SegmentPath::join($folder, $filename);

        if ($this->slugExists($slug, $themeSlug)) {
            $base = $filename;
            $suffix = 2;

            while ($this->slugExists(SegmentPath::join($folder, $base.'-'.$suffix), $themeSlug)) {
                $suffix++;
            }

            $slug = SegmentPath::join($folder, $base.'-'.$suffix);
        }

        return $slug;
    }

    protected function generateSlug(string $name, ?string $themeSlug = null): string
    {
        return SegmentPath::slugifySegment($name);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $path = $this->filePath($slug, $themeSlug);
        $parentDir = dirname($path);

        if (! is_dir($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }

        parent::write($slug, $data, $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function buildTreeNode(string $baseDir, string $relativePath, array &$node): void
    {
        $currentDir = $relativePath === '' ? $baseDir : $baseDir.'/'.$relativePath;

        if (! is_dir($currentDir)) {
            return;
        }

        $entries = scandir($currentDir);

        if ($entries === false) {
            return;
        }

        $folders = [];
        $segments = [];
        $extension = '.'.$this->fileExtension();

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $currentDir.'/'.$entry;
            $entryRelative = $relativePath === '' ? $entry : $relativePath.'/'.$entry;

            if (is_dir($entryPath)) {
                $folders[] = $entry;

                continue;
            }

            if (str_ends_with($entry, $extension)) {
                $slug = SegmentPath::normalize(substr($entryRelative, 0, -strlen($extension)));
                $record = $this->find($slug);

                $segments[] = [
                    'name' => $record?->name ?? SegmentPath::basename($slug),
                    'path' => $slug,
                    'type' => 'segment',
                    'slug' => $slug,
                    'enabled' => (bool) ($record?->enabled ?? true),
                    'parameters' => array_map(
                        fn (array $p) => [
                            'name' => $p['name'] ?? '',
                            'type' => $p['type'] ?? 'text',
                            'default' => $p['default'] ?? null,
                        ],
                        $record?->code['parameters'] ?? []
                    ),
                ];
            }
        }

        sort($folders);

        foreach ($folders as $folder) {
            $folderPath = $relativePath === '' ? $folder : $relativePath.'/'.$folder;
            $child = [
                'name' => $folder,
                'path' => $folderPath,
                'type' => 'folder',
                'children' => [],
            ];
            $this->buildTreeNode($baseDir, $folderPath, $child);
            usort($child['children'], fn (array $a, array $b) => $this->compareTreeNodes($a, $b));
            $node['children'][] = $child;
        }

        foreach ($segments as $segment) {
            $node['children'][] = $segment;
        }
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    protected function compareTreeNodes(array $a, array $b): int
    {
        $typeOrder = ['folder' => 0, 'segment' => 1];
        $typeCompare = ($typeOrder[$a['type'] ?? ''] ?? 2) <=> ($typeOrder[$b['type'] ?? ''] ?? 2);

        if ($typeCompare !== 0) {
            return $typeCompare;
        }

        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    }
}
