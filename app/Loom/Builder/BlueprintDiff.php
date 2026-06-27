<?php

namespace Loom\Builder;

use Illuminate\Support\Facades\Schema;

class BlueprintDiff
{
    public function __construct(
        protected SchemaGenerator $schemas,
        protected MigrationGenerator $migrations,
        protected GeneratedModelWriter $models,
        protected SecureFileWriter $writer,
        protected PluginImporter $importer,
    ) {}

    /**
     * @return array{
     *   files: list<array{path: string, action: string, preview?: string}>,
     *   warnings: list<string>,
     *   migrations: list<string>
     * }
     */
    public function diff(Blueprint $blueprint): array
    {
        $slug = $blueprint->pluginSlug();
        $files = [];
        $warnings = [];
        $migrations = [];

        $schemaFiles = $this->schemas->generate($blueprint);

        foreach ($schemaFiles as $relative => $content) {
            $action = $this->writer->exists($slug, $relative) ? 'update' : 'create';
            $files[] = [
                'path' => $relative,
                'action' => $action,
                'preview' => $this->truncate($content),
            ];
        }

        $traitFiles = $this->models->generate($blueprint);

        foreach ($traitFiles as $relative => $content) {
            $action = $this->writer->exists($slug, $relative) ? 'update' : 'create';
            $files[] = [
                'path' => $relative,
                'action' => $action,
                'preview' => $this->truncate($content),
            ];
        }

        $modelPath = 'models/'.$blueprint->modelClass().'.php';

        if ($blueprint->isNewPlugin() && ! $this->writer->exists($slug, $modelPath)) {
            $files[] = [
                'path' => $modelPath,
                'action' => 'create',
                'preview' => $this->truncate($this->models->scaffoldUserModel($blueprint)),
            ];
        }

        $existingColumns = $this->importer->existingTableColumns($blueprint);
        $columnSqlTypes = $this->importer->existingColumnSqlTypes($blueprint);
        $isCreate = ! Schema::hasTable($blueprint->tableName());
        $previousFields = $blueprint->isNewPlugin()
            ? []
            : $this->importer->import($blueprint->identifier())->modelFields();
        $migrationFiles = $this->migrations->generate(
            $blueprint,
            $existingColumns,
            $isCreate,
            $previousFields,
            $columnSqlTypes
        );

        foreach ($migrationFiles as $relative => $content) {
            $files[] = [
                'path' => $relative,
                'action' => 'create',
                'preview' => $this->truncate($content),
            ];
            $migrations[] = $relative;
        }

        if ($blueprint->isNewPlugin()) {
            foreach ($this->scaffoldFiles($blueprint) as $relative => $content) {
                if (! $this->writer->exists($slug, $relative)) {
                    $files[] = [
                        'path' => $relative,
                        'action' => 'create',
                        'preview' => $this->truncate($content),
                    ];
                }
            }
        }

        return [
            'files' => $files,
            'warnings' => $warnings,
            'migrations' => $migrations,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function scaffoldFiles(Blueprint $blueprint): array
    {
        return app(PluginScaffolder::class)->scaffoldFiles($blueprint);
    }

    protected function truncate(string $content, int $length = 600): string
    {
        if (strlen($content) <= $length) {
            return $content;
        }

        return substr($content, 0, $length)."\n...";
    }
}
