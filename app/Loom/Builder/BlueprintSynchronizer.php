<?php

namespace Loom\Builder;

use Loom\System\PluginManager;

class BlueprintSynchronizer
{
    public function __construct(
        protected SecureFileWriter $writer,
        protected SchemaGenerator $schemas,
        protected GeneratedModelWriter $models,
        protected PluginScaffolder $scaffolder,
        protected PluginPhpPatcher $phpPatcher,
        protected PluginRoutesPatcher $routesPatcher,
        protected PluginManifestGenerator $manifest,
        protected PluginMigrationRunner $migrations,
        protected PluginImporter $importer,
        protected PluginManager $plugins,
    ) {}

    /**
     * @return array{migrate_output: string, migration_failed: bool}
     */
    public function sync(Blueprint $blueprint): array
    {
        $slug = $blueprint->pluginSlug();

        if ($slug === '' || ! preg_match('/^[a-z][a-z0-9-]*$/', $slug)) {
            throw new \InvalidArgumentException('Invalid plugin slug.');
        }

        if (! $this->pluginExistsOnDisk($blueprint)) {
            if (! $blueprint->isNewPlugin()) {
                return ['migrate_output' => '', 'migration_failed' => false];
            }

            $this->scaffoldPlugin($blueprint);
        }

        $previousFields = $this->previousModelFields($blueprint);

        $this->writeGeneratedFiles($blueprint);

        $migrateOutput = $this->migrations->generateAndRun($blueprint, $previousFields);

        $this->plugins->cachePluginList();

        return [
            'migrate_output' => $migrateOutput,
            'migration_failed' => $this->migrations->failed($migrateOutput),
        ];
    }

    protected function pluginExistsOnDisk(Blueprint $blueprint): bool
    {
        return is_dir($this->writer->pluginRoot($blueprint->pluginSlug()))
            && $this->writer->exists($blueprint->pluginSlug(), 'Plugin.php');
    }

    protected function scaffoldPlugin(Blueprint $blueprint): void
    {
        $slug = $blueprint->pluginSlug();

        foreach ($this->scaffolder->scaffoldFiles($blueprint) as $relative => $content) {
            if (! $this->writer->exists($slug, $relative)) {
                $this->writer->write($slug, $relative, $content, false);
            }
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
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function previousModelFields(Blueprint $blueprint): array
    {
        if (! $this->pluginExistsOnDisk($blueprint)) {
            return [];
        }

        try {
            return $this->importer->import($blueprint->identifier())->modelFields();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function writeGeneratedFiles(Blueprint $blueprint): void
    {
        $slug = $blueprint->pluginSlug();

        foreach ($this->schemas->generate($blueprint) as $relative => $content) {
            $this->writer->write($slug, $relative, $content, true);
        }

        foreach ($this->models->generate($blueprint) as $relative => $content) {
            $this->writer->write($slug, $relative, $content, true);
        }

        $this->writer->write(
            $slug,
            'plugin.yaml',
            $this->manifest->generate($blueprint),
            true
        );

        $pluginPhp = $this->phpPatcher->patch($blueprint);

        if ($pluginPhp !== null) {
            $this->writer->write($slug, 'Plugin.php', $pluginPhp, true);
        }

        $routesPhp = $this->routesPatcher->patch($blueprint);

        if ($routesPhp !== null) {
            $this->writer->write($slug, 'routes.php', $routesPhp, true);
        }
    }
}
