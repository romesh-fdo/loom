<?php

namespace Loom\Builder;

use Illuminate\Support\Facades\Artisan;
use Loom\System\PluginManager;

class BlueprintApplier
{
    public function __construct(
        protected SecureFileWriter $writer,
        protected SchemaGenerator $schemas,
        protected MigrationGenerator $migrations,
        protected GeneratedModelWriter $models,
        protected PluginScaffolder $scaffolder,
        protected PluginImporter $importer,
        protected PluginManager $plugins,
    ) {}

    /**
     * @return array{success: bool, message: string, migrate_output?: string}
     */
    public function apply(Blueprint $blueprint): array
    {
        $slug = $blueprint->pluginSlug();

        if ($slug === '' || ! preg_match('/^[a-z][a-z0-9-]*$/', $slug)) {
            throw new \InvalidArgumentException('Invalid plugin slug.');
        }

        foreach ($this->scaffolder->scaffoldFiles($blueprint) as $relative => $content) {
            if ($blueprint->isNewPlugin() && ! $this->writer->exists($slug, $relative)) {
                $this->writer->write($slug, $relative, $content, false);
            }
        }

        foreach ($this->schemas->generate($blueprint) as $relative => $content) {
            $this->writer->write($slug, $relative, $content, true);
        }

        foreach ($this->models->generate($blueprint) as $relative => $content) {
            $this->writer->write($slug, $relative, $content, true);
        }

        $modelPath = 'models/'.$blueprint->modelClass().'.php';

        if (! $this->writer->exists($slug, $modelPath)) {
            $this->writer->write(
                $slug,
                $modelPath,
                $this->models->scaffoldUserModel($blueprint),
                false
            );
        }

        $existingColumns = $this->importer->existingTableColumns($blueprint);
        $isCreate = $blueprint->isNewPlugin() && $existingColumns === [];

        foreach ($this->migrations->generate($blueprint, $existingColumns, $isCreate) as $relative => $content) {
            if (! $this->writer->exists($slug, $relative)) {
                $this->writer->write($slug, $relative, $content, false);
            }
        }

        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        if (str_contains(strtolower($migrateOutput), 'fail') && str_contains(strtolower($migrateOutput), 'error')) {
            return [
                'success' => false,
                'message' => 'Migration may have failed. Review output.',
                'migrate_output' => $migrateOutput,
            ];
        }

        $this->plugins->cachePluginList();

        return [
            'success' => true,
            'message' => 'Blueprint applied successfully.',
            'migrate_output' => trim($migrateOutput),
        ];
    }
}
