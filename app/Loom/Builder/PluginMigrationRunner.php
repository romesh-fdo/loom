<?php

namespace Loom\Builder;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class PluginMigrationRunner
{
    public function __construct(
        protected MigrationGenerator $generator,
        protected PluginImporter $importer,
        protected SecureFileWriter $writer,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $previousFields
     */
    public function generatePending(Blueprint $blueprint, array $previousFields = []): void
    {
        $existingColumns = $this->importer->existingTableColumns($blueprint);
        $columnSqlTypes = $this->importer->existingColumnSqlTypes($blueprint);
        $isCreate = ! Schema::hasTable($blueprint->tableName());

        foreach ($this->generator->generate(
            $blueprint,
            $existingColumns,
            $isCreate,
            $previousFields,
            $columnSqlTypes
        ) as $relative => $content) {
            if (! $this->writer->exists($blueprint->pluginSlug(), $relative)) {
                $this->writer->write($blueprint->pluginSlug(), $relative, $content, false);
            }
        }
    }

    public function run(Blueprint $blueprint): string
    {
        $path = $this->relativeMigrationPath($blueprint);

        if ($path === null) {
            return '';
        }

        Artisan::call('migrate', [
            '--force' => true,
            '--path' => $path,
        ]);

        return trim(Artisan::output());
    }

    /**
     * @param  list<array<string, mixed>>  $previousFields
     */
    public function generateAndRun(Blueprint $blueprint, array $previousFields = []): string
    {
        $this->generatePending($blueprint, $previousFields);

        return $this->run($blueprint);
    }

    public function failed(string $output): bool
    {
        $normalized = strtolower($output);

        return $output !== ''
            && str_contains($normalized, 'fail')
            && str_contains($normalized, 'error');
    }

    protected function relativeMigrationPath(Blueprint $blueprint): ?string
    {
        $updatesPath = $blueprint->pluginPath().'/updates';

        if (! is_dir($updatesPath)) {
            return null;
        }

        $files = glob($updatesPath.'/*.php');

        if ($files === false || $files === []) {
            return null;
        }

        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $absolute = str_replace('\\', '/', $updatesPath);

        if (! str_starts_with($absolute, $base.'/')) {
            return null;
        }

        return substr($absolute, strlen($base) + 1);
    }
}
