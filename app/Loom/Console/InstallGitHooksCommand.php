<?php

namespace Loom\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallGitHooksCommand extends Command
{
    protected $signature = 'loom:install-git-hooks';

    protected $description = 'Install local git hooks (strips Cursor commit attribution)';

    public function handle(): int
    {
        if (! is_dir(base_path('.git/hooks'))) {
            $this->components->error('Not a git repository or .git/hooks is missing.');

            return self::FAILURE;
        }

        $hooks = [
            'prepare-commit-msg',
            'commit-msg',
        ];

        foreach ($hooks as $hook) {
            $source = base_path("scripts/git-hooks/{$hook}");
            $target = base_path(".git/hooks/{$hook}");

            if (! file_exists($source)) {
                $this->components->error("Hook source not found: scripts/git-hooks/{$hook}");

                return self::FAILURE;
            }

            File::copy($source, $target);

            if (PHP_OS_FAMILY !== 'Windows') {
                chmod($target, 0755);
            }

            $this->line("  <fg=gray>Installed {$hook}</>");
        }

        $this->components->info('Git hooks installed.');
        $this->line('  <fg=gray>Removes Cursor co-author / attribution from commit messages</>');

        return self::SUCCESS;
    }
}
