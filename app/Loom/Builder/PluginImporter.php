<?php

namespace Loom\Builder;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Loom\Support\FormSchema;
use Loom\System\PluginManager;

class PluginImporter
{
    public function __construct(
        protected PluginManager $plugins,
    ) {}

    public function import(string $pluginIdentifier): Blueprint
    {
        $plugin = $this->plugins->getPlugin($pluginIdentifier);

        if ($plugin === null) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginIdentifier}");
        }

        $slug = $plugin->getName();
        $path = $plugin->getPluginPath();
        $manifest = $this->readPluginManifest($path);
        $forms = [];

        foreach ($plugin->registerForms() as $formKey => $definition) {
            if ($formKey !== 'basic-form') {
                continue;
            }

            $schemaName = $definition['schema'] ?? 'basic';
            $schema = FormSchema::load($pluginIdentifier, $schemaName);
            $meta = $schema['meta'] ?? [];
            $storage = 'model';

            $fields = [];

            foreach ($schema['fields'] ?? [] as $name => $field) {
                if (($field['section'] ?? 'block') === 'demo') {
                    continue;
                }

                $fieldStorage = 'model';
                $validation = $field['validation'] ?? FieldTypeRegistry::defaultValidation($field['type'] ?? 'text');
                $schemaMessages = $field['validation_messages'] ?? [];

                if (! is_array($schemaMessages)) {
                    $schemaMessages = [];
                }

                $validationRules = [];

                foreach ($validation as $rule) {
                    if (! is_string($rule) || $rule === '') {
                        continue;
                    }

                    $messageKey = Str::before($rule, ':');
                    $message = '';

                    if (array_is_list($schemaMessages)) {
                        foreach ($schemaMessages as $entry) {
                            if (! is_array($entry)) {
                                continue;
                            }

                            $entryRule = (string) ($entry['rule'] ?? '');

                            if ($entryRule === $rule || $entryRule === $messageKey) {
                                $message = (string) ($entry['message'] ?? '');
                                break;
                            }
                        }
                    } else {
                        $message = (string) ($schemaMessages[$messageKey] ?? $schemaMessages[$rule] ?? '');
                    }

                    $validationRules[] = [
                        'rule' => $rule,
                        'message' => $message,
                    ];
                }

                $importedField = [
                    'name' => $name,
                    'type' => $field['type'] ?? $field['input'] ?? 'text',
                    'label' => $field['label'] ?? Str::headline($name),
                    'storage' => $fieldStorage,
                    'validation' => $validation,
                    'validation_rules' => $validationRules,
                    'colClass' => $field['colClass'] ?? 'col-12',
                    'help' => $field['help'] ?? null,
                    'placeholder' => $field['placeholder'] ?? null,
                ];

                $fields[] = $importedField;
            }

            if ($fields !== []) {
                $forms[] = [
                    'key' => 'basic-form',
                    'schema' => 'basic',
                    'storage' => $storage,
                    'layout' => $meta['layout'] ?? 'panel',
                    'fields' => $fields,
                ];
            }
        }

        $modelMeta = $this->inferModelMeta($pluginIdentifier, $slug, $path);

        return Blueprint::fromArray([
            'is_new' => false,
            'plugin' => [
                'name' => $slug,
                'label' => $manifest['name'] ?? $plugin->pluginDetails()['name'] ?? Str::headline($slug),
                'route' => $manifest['route'] ?? $this->inferRouteSlug($slug),
                'icon' => $manifest['icon'] ?? 'bi-box',
            ],
            'model' => $modelMeta,
            'forms' => $forms,
        ], $pluginIdentifier, 'draft');
    }

    /**
     * @return list<string>
     */
    public function existingTableColumns(Blueprint $blueprint): array
    {
        $table = $blueprint->tableName();

        if (! Schema::hasTable($table)) {
            return [];
        }

        return Schema::getColumnListing($table);
    }

    /**
     * @return array<string, string> column name => normalized sql type
     */
    public function existingColumnSqlTypes(Blueprint $blueprint): array
    {
        $table = $blueprint->tableName();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $types = [];

        foreach (Schema::getColumnListing($table) as $column) {
            $types[$column] = $this->normalizeSqlType(Schema::getColumnType($table, $column));
        }

        return $types;
    }

    public function normalizeSqlType(string $dbType): string
    {
        $dbType = strtolower($dbType);

        return match (true) {
            in_array($dbType, ['text', 'mediumtext', 'longtext'], true) => 'text',
            in_array($dbType, ['int', 'integer', 'bigint', 'smallint'], true) => 'integer',
            in_array($dbType, ['tinyint', 'boolean', 'bool'], true) => 'boolean',
            $dbType === 'json' => 'json',
            default => 'string',
        };
    }

    public function resolveTableName(string $pluginPath, string $slug): string
    {
        $manifest = $this->readPluginManifest($pluginPath);

        if (! empty($manifest['table'])) {
            return TableNames::applyPrefix((string) $manifest['table']);
        }

        return TableNames::defaultForSlug($slug);
    }

    protected function inferRouteSlug(string $slug): string
    {
        return match ($slug) {
            'blocks' => 'blocks',
            'pages' => 'pages',
            default => Str::plural($slug),
        };
    }

    /**
     * @return array{class: string, table: string}
     */
    protected function inferModelMeta(string $pluginIdentifier, string $slug, string $path): array
    {
        $manifest = $this->readPluginManifest($path);
        $studly = Str::studly($slug);
        $modelClass = $manifest['model'] ?? Str::singular($studly);
        $table = $this->resolveTableName($path, $slug);

        $generatedPath = "{$path}/models/Generated/{$modelClass}Schema.php";

        if (file_exists($generatedPath)) {
            $content = file_get_contents($generatedPath);

            if (preg_match("/return '([^']+)';\s*\}\s*\n\s*public function initialize/", $content, $matches)) {
                $table = $matches[1];
            } elseif (preg_match("/getTable\(\): string\s*\{\s*return '([^']+)'/", $content, $matches)) {
                $table = $matches[1];
            }
        } else {
            $modelFile = "{$path}/models/{$modelClass}.php";

            if (file_exists($modelFile)) {
                $content = file_get_contents($modelFile);

                if (preg_match("/protected \\\$table = '([^']+)'/", $content, $matches)) {
                    $table = $matches[1];
                }
            }
        }

        return [
            'class' => $modelClass === 'Block' ? 'Block' : $modelClass,
            'table' => $table,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function readPluginManifest(string $pluginPath): array
    {
        $manifestPath = $pluginPath.'/plugin.yaml';

        if (! file_exists($manifestPath)) {
            return [];
        }

        $manifest = [];

        foreach (file($manifestPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $manifest[$key] = trim($value, " \t\"'");
        }

        return $manifest;
    }
}
