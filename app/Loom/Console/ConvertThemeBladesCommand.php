<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Loom\Support\ThemeContent\BlockStore;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeContent\SegmentStore;
use Loom\Support\ThemeManager;

class ConvertThemeBladesCommand extends Command
{
    protected $signature = 'loom:convert-theme-blades';

    protected $description = 'Convert legacy block/segment/page JSON files to blade.php format';

    public function handle(
        BlockStore $blocks,
        SegmentStore $segments,
        PageStore $pages,
        ThemeManager $themes,
    ): int {
        $converted = 0;

        foreach ($themes->all() as $theme) {
            $slug = $theme['slug'] ?? null;

            if (! is_string($slug)) {
                continue;
            }

            $blocks->all($slug);
            $segments->all($slug);
            $pages->all($slug);
            $converted++;
        }

        $this->components->info("Processed {$converted} theme(s). Legacy JSON files were converted to blade.php where found.");

        return self::SUCCESS;
    }
}
