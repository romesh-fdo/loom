<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Loom\Builder\TableNames;
use Loom\Support\ThemeContent\BlockStore;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeManager;

class ExportThemeContentCommand extends Command
{
    protected $signature = 'loom:export-theme-content';

    protected $description = 'Export loom_blocks and loom_pages database rows to theme files';

    public function handle(
        ThemeManager $themes,
        BlockStore $blocks,
        PageStore $pages,
    ): int {
        $blocksTable = TableNames::applyPrefix('blocks');
        $pagesTable = TableNames::applyPrefix('pages');

        if (! $this->tableExists($blocksTable) && ! $this->tableExists($pagesTable)) {
            $this->components->warn('No loom_blocks or loom_pages tables found. Nothing to export.');

            return self::SUCCESS;
        }

        $exportedBlocks = 0;
        $exportedPages = 0;
        $blockIdToSlug = [];

        if ($this->tableExists($blocksTable)) {
            $rows = DB::table($blocksTable)->orderBy('id')->get();

            foreach ($rows as $row) {
                $themeSlug = (string) ($row->theme_slug ?? ThemeManager::DEFAULT_SLUG);
                $name = (string) $row->name;
                $slug = Str::slug($name);

                if ($slug === '') {
                    $slug = 'block-'.$row->id;
                }

                $base = $slug;
                $suffix = 2;
                $themes->ensureContentDirectories($themeSlug);

                while ($blocks->slugExists($slug, $themeSlug)) {
                    $slug = $base.'-'.$suffix;
                    $suffix++;
                }

                $code = json_decode((string) ($row->code ?? ''), true);
                $config = json_decode((string) ($row->config ?? ''), true);

                $blocks->create([
                    'name' => $name,
                    'slug' => $slug,
                    'code' => is_array($code) ? $code : ['template' => '', 'parameters' => []],
                    'config' => is_array($config) ? $config : null,
                    'updated_at' => $row->updated_at ?? now()->toIso8601String(),
                ], $themeSlug);

                $blockIdToSlug[(int) $row->id] = $slug;
                $exportedBlocks++;
            }
        }

        if ($this->tableExists($pagesTable)) {
            $rows = DB::table($pagesTable)->orderBy('id')->get();

            foreach ($rows as $row) {
                $themeSlug = (string) ($row->theme_slug ?? ThemeManager::DEFAULT_SLUG);
                $url = strtolower(trim((string) $row->url, '/'));
                $slug = Str::slug($url);

                if ($slug === '') {
                    $slug = 'page-'.$row->id;
                }

                $themes->ensureContentDirectories($themeSlug);
                $dir = $themes->themesPath().'/'.$themeSlug.'/pages';

                $sections = json_decode((string) ($row->sections ?? ''), true);
                $normalizedSections = [];

                if (is_array($sections)) {
                    foreach ($sections as $section) {
                        if (! is_array($section)) {
                            continue;
                        }

                        $blockSlug = null;

                        if (! empty($section['block_slug'])) {
                            $blockSlug = (string) $section['block_slug'];
                        } elseif (! empty($section['block_id'])) {
                            $blockSlug = $blockIdToSlug[(int) $section['block_id']] ?? null;
                        }

                        if ($blockSlug === null) {
                            continue;
                        }

                        $normalizedSections[] = [
                            'block_slug' => $blockSlug,
                            'values' => is_array($section['values'] ?? null) ? $section['values'] : [],
                        ];
                    }
                }

                $payload = [
                    'name' => (string) $row->name,
                    'slug' => $slug,
                    'url' => $url,
                    'layout' => (string) ($row->layout ?? ''),
                    'layout_fields' => is_array(json_decode((string) ($row->layout_fields ?? ''), true))
                        ? json_decode((string) ($row->layout_fields ?? ''), true)
                        : [],
                    'sections' => $normalizedSections,
                    'updated_at' => $row->updated_at ?? now()->toIso8601String(),
                ];

                $pages->create($payload, $themeSlug);

                $exportedPages++;
            }
        }

        $this->components->info("Exported {$exportedBlocks} block(s) and {$exportedPages} page(s) to theme folders.");

        return self::SUCCESS;
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
