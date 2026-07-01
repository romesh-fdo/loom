<?php

namespace Loom\Support\ThemeContent;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PageStore extends ThemeBladeStore
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

    protected function legacyPageJsonPath(string $slug, ?string $themeSlug = null): string
    {
        return $this->pageDirPath($slug, $themeSlug).'/'.self::PAGE_JSON_FILENAME;
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

            if ($pageUrl === $normalized) {
                return true;
            }

            if (PageUrlPattern::isPattern($pageUrl) || PageUrlPattern::isPattern($normalized)) {
                return PageUrlPattern::templateKey($pageUrl) === PageUrlPattern::templateKey($normalized);
            }

            return false;
        });
    }

    public function findByUrl(string $url, ?string $themeSlug = null): ?ThemeFileRecord
    {
        return $this->matchPath($url, $themeSlug)?->page;
    }

    public function matchPath(string $path, ?string $themeSlug = null): ?PagePathMatch
    {
        $normalized = $this->normalizePageUrl($path);
        $pages = $this->all($themeSlug);

        foreach ($pages as $record) {
            $pageUrl = isset($record->url) && is_string($record->url)
                ? $this->normalizePageUrl($record->url)
                : '';

            if ($pageUrl === $normalized && ! PageUrlPattern::isPattern($pageUrl)) {
                return new PagePathMatch($record, []);
            }
        }

        $bestMatch = null;
        $bestScore = -1;

        foreach ($pages as $record) {
            $pageUrl = isset($record->url) && is_string($record->url)
                ? $this->normalizePageUrl($record->url)
                : '';

            if (! PageUrlPattern::isPattern($pageUrl)) {
                continue;
            }

            $params = PageUrlPattern::match($pageUrl, $normalized);

            if ($params === null) {
                continue;
            }

            $score = (PageUrlPattern::staticSegmentCount($pageUrl) * 1000) + strlen($pageUrl);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = new PagePathMatch($record, $params);
            }
        }

        return $bestMatch;
    }

    /**
     * @return Collection<int, ThemeFileRecord>
     */
    public function all(?string $themeSlug = null): Collection
    {
        $records = parent::all($themeSlug);
        $seen = $records->mapWithKeys(fn (ThemeFileRecord $record) => [$record->slug => true])->all();
        $dir = $this->dirForTheme($themeSlug);

        if (! is_dir($dir)) {
            return $records;
        }

        foreach (glob($dir.'/*/'.self::PAGE_JSON_FILENAME) ?: [] as $path) {
            $slug = basename(dirname($path));

            if (isset($seen[$slug])) {
                continue;
            }

            $record = $this->readLegacyPageJsonFile($path, $slug, $themeSlug);

            if ($record !== null) {
                $records->push($record);
                $seen[$slug] = true;
            }
        }

        return $records->sortByDesc(fn (ThemeFileRecord $record) => $record->updatedAt()->timestamp)->values();
    }

    public function find(string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $record = parent::find($slug, $themeSlug);

        if ($record !== null) {
            return $record;
        }

        $legacyPageJson = $this->legacyPageJsonPath($slug, $themeSlug);

        if (file_exists($legacyPageJson)) {
            return $this->readLegacyPageJsonFile($legacyPageJson, $slug, $themeSlug);
        }

        return null;
    }

    public function slugExists(string $slug, ?string $themeSlug = null, ?string $ignoreSlug = null): bool
    {
        if ($ignoreSlug !== null && $slug === $ignoreSlug) {
            return false;
        }

        return parent::slugExists($slug, $themeSlug, $ignoreSlug)
            || file_exists($this->legacyPageJsonPath($slug, $themeSlug))
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

        if ($this->find($slug, $themeSlug) === null) {
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

        if ($url === '') {
            if ($this->slugExists(self::HOME_PAGE_SLUG, $themeSlug)) {
                throw new InvalidArgumentException('A homepage already exists for this theme.');
            }

            return self::HOME_PAGE_SLUG;
        }

        return $this->generateSlug((string) ($data['name'] ?? 'page'), $themeSlug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSlugForUpdate(string $currentSlug, array $data, string $themeSlug): string
    {
        $url = $this->normalizePageUrl($data['url'] ?? '');

        if ($url === '') {
            $slug = self::HOME_PAGE_SLUG;
        } else {
            $slug = Str::slug(trim((string) ($data['name'] ?? '')));

            if ($slug === '') {
                return $currentSlug;
            }
        }

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

        if (! isset($data['layout_fields']) || ! is_array($data['layout_fields'])) {
            $data['layout_fields'] = [];
        }

        if (! isset($data['entity_imports']) || ! is_array($data['entity_imports'])) {
            $data['entity_imports'] = [];
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

        if (! isset($data['layout_fields']) || ! is_array($data['layout_fields'])) {
            $data['layout_fields'] = [];
        }

        if (! isset($data['entity_imports']) || ! is_array($data['entity_imports'])) {
            $data['entity_imports'] = [];
        }
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
            'url' => isset($data['url']) && is_string($data['url'])
                ? $this->normalizePageUrl($data['url'])
                : '',
            'layout' => (string) ($data['layout'] ?? ''),
        ];
    }

    protected function readFile(string $path, string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $parsed = PageBladeDocument::parse((string) file_get_contents($path));
        $data = $this->metaToRecordData(
            $slug,
            $parsed['meta'],
            $parsed['template'],
            $parsed['layout_fields'],
            $parsed['entity_imports']
        );
        $data['slug'] = $data['slug'] ?? $slug;

        if (isset($data['updated_at']) && is_string($data['updated_at'])) {
            $data['updated_at'] = Carbon::parse($data['updated_at']);
        } else {
            $data['updated_at'] = Carbon::createFromTimestamp(filemtime($path));
        }

        return new ThemeFileRecord($slug, $data);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, array<string, mixed>>  $layoutFields
     * @param  list<array<string, mixed>>  $entityImports
     * @return array<string, mixed>
     */
    protected function metaToRecordData(string $slug, array $meta, string $template, array $layoutFields = [], array $entityImports = []): array
    {
        $sections = self::sectionsFromTemplate($template);

        return [
            'name' => $meta['name'] ?? $slug,
            'slug' => $meta['slug'] ?? $slug,
            'url' => isset($meta['url']) && is_string($meta['url'])
                ? $this->normalizePageUrl($meta['url'])
                : '',
            'layout' => (string) ($meta['layout'] ?? ''),
            'entity_imports' => $entityImports,
            'layout_fields' => $layoutFields,
            'sections' => $sections,
            'code' => ['template' => $template],
            'updated_at' => $meta['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $slug, array $data, ?string $themeSlug = null): void
    {
        $this->ensureDir($themeSlug);

        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $template = self::templateFromSections($sections);
        $layoutFields = is_array($data['layout_fields'] ?? null) ? $data['layout_fields'] : [];
        $entityImports = is_array($data['entity_imports'] ?? null) ? array_values($data['entity_imports']) : [];
        $meta = $this->buildMeta($data);
        $meta['updated_at'] = ($data['updated_at'] ?? null) instanceof Carbon
            ? $data['updated_at']->toIso8601String()
            : (is_string($data['updated_at'] ?? null) ? $data['updated_at'] : now()->toIso8601String());

        File::put(
            $this->filePath($slug, $themeSlug),
            PageBladeDocument::compose($meta, $entityImports, $layoutFields, $template)
        );
    }

    protected function readLegacyPageJsonFile(string $path, string $slug, ?string $themeSlug = null): ?ThemeFileRecord
    {
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return null;
        }

        $data['slug'] = $slug;

        if (! isset($data['layout_fields']) || ! is_array($data['layout_fields'])) {
            $data['layout_fields'] = [];
        }

        if (! isset($data['entity_imports']) || ! is_array($data['entity_imports'])) {
            $data['entity_imports'] = [];
        }

        $this->write($slug, $data, $themeSlug);
        $this->removeLegacyPageDir($slug, $themeSlug);

        return $this->find($slug, $themeSlug);
    }

    protected function removePageStorage(string $slug, ?string $themeSlug = null): void
    {
        $path = $this->filePath($slug, $themeSlug);

        if (file_exists($path)) {
            File::delete($path);
        }

        $this->removeLegacyPageDir($slug, $themeSlug);

        $legacy = $this->legacyJsonPath($slug, $themeSlug);

        if (file_exists($legacy)) {
            File::delete($legacy);
        }
    }

    protected function removeLegacyPageDir(string $slug, ?string $themeSlug = null): void
    {
        $pageDir = $this->pageDirPath($slug, $themeSlug);

        if (! is_dir($pageDir)) {
            return;
        }

        $jsonPath = $pageDir.'/'.self::PAGE_JSON_FILENAME;

        if (file_exists($jsonPath)) {
            File::delete($jsonPath);
        }

        if (count(scandir($pageDir)) === 2) {
            File::deleteDirectory($pageDir);
        }
    }

    /**
     * @return list<array{block_slug: string, values: array<string, mixed>}>
     */
    public static function sectionsFromTemplate(string $template): array
    {
        return collect(ThemeDirectiveParser::parseBlockDirectives($template))
            ->map(fn (array $directive) => [
                'block_slug' => $directive['blockSlug'],
                'values' => $directive['values'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{block_slug?: string, values?: array<string, mixed>}>  $sections
     */
    public static function templateFromSections(array $sections): string
    {
        $lines = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $blockSlug = $section['block_slug'] ?? null;

            if (! is_string($blockSlug) || $blockSlug === '') {
                continue;
            }

            $values = is_array($section['values'] ?? null) ? $section['values'] : [];
            $lines[] = ThemeDirectiveParser::formatBlockDirective($blockSlug, $values);
        }

        return implode(PHP_EOL.PHP_EOL, $lines);
    }

    protected function normalizePageUrl(mixed $url): string
    {
        if (! is_string($url)) {
            return '';
        }

        return strtolower(trim($url, '/'));
    }
}
