<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Loom\System\PluginManager;

class CachePluginsCommand extends Command
{
    protected $signature = 'loom:cache';

    protected $description = 'Cache the discovered Loom plugin list for faster boot';

    public function handle(PluginManager $manager): int
    {
        $path = $manager->cachePluginList();

        $this->components->info('Loom plugins cached successfully.');
        $this->line("  <fg=gray>{$path}</>");

        return self::SUCCESS;
    }
}
