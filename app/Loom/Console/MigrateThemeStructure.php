<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateThemeStructure extends Command
{
    protected $signature = 'loom:theme-migrate';

    protected $description = 'Migrate flat theme/assets into theme/default/ with a theme.json manifest';

    public function handle(): int
    {
        $themeRoot = base_path('theme');
        $legacyAssets = $themeRoot.'/assets';
        $defaultDir = $themeRoot.'/default';
        $defaultAssets = $defaultDir.'/assets';
        $manifestPath = $defaultDir.'/theme.json';

        if (! is_dir($themeRoot)) {
            File::makeDirectory($themeRoot, 0755, true);
        }

        if (! is_dir($defaultDir)) {
            File::makeDirectory($defaultDir, 0755, true);
        }

        if (is_dir($legacyAssets)) {
            if (is_dir($defaultAssets)) {
                $this->components->warn('theme/default/assets already exists — merging legacy theme/assets into it.');

                File::copyDirectory($legacyAssets, $defaultAssets);
            } else {
                File::moveDirectory($legacyAssets, $defaultAssets);
            }

            if (is_dir($legacyAssets) && $this->directoryIsEmpty($legacyAssets)) {
                File::deleteDirectory($legacyAssets);
            }

            $this->components->info('Moved theme/assets to theme/default/assets.');
        } elseif (! is_dir($defaultAssets)) {
            File::makeDirectory($defaultAssets, 0755, true);
            $this->components->info('Created empty theme/default/assets.');
        }

        if (! file_exists($manifestPath)) {
            File::put($manifestPath, json_encode([
                'name' => 'Default',
                'slug' => 'default',
                'description' => 'Default Loom theme',
                'version' => '1.0.0',
                'author' => 'Loom',
                'created_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

            $this->components->info('Created theme/default/theme.json.');
        }

        $this->components->info('Theme structure migration complete.');

        return self::SUCCESS;
    }

    protected function directoryIsEmpty(string $path): bool
    {
        $iterator = new \FilesystemIterator($path);

        return ! $iterator->valid();
    }
}
