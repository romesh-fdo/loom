<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Loom\System\PluginManager;

class ClearPluginsCommand extends Command
{
    protected $signature = 'loom:clear';

    protected $description = 'Clear the cached Loom plugin list';

    public function handle(PluginManager $manager): int
    {
        if ($manager->clearPluginCache()) {
            $this->components->info('Loom plugin cache cleared.');
        } else {
            $this->components->warn('No Loom plugin cache file found.');
        }

        return self::SUCCESS;
    }
}
