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
        $forms = [];

        foreach ($plugin->registerForms() as $formKey => $definition) {
            $schemaName = $definition['schema'] ?? 'basic';
            $schema = FormSchema::load($pluginIdentifier, $schemaName);
            $meta = $schema['meta'];
            $storage = $meta['storage'] ?? 'model';

            $fields = [];

            foreach ($schema['fields'] ?? [] as $name => $field) {
                if (($field['section'] ?? 'block') === 'demo') {
                    continue;
                }

                $fieldStorage = $storage === 'config' ? 'config' : 'model';

                $fields[] = [
                    'name' => $name,
                    'type' => $field['type'] ?? $field['input'] ?? 'text',
                    'label' => $field['label'] ?? Str::headline($name),
                    'storage' => $fieldStorage,
                    'validation' => $field['validation'] ?? FieldTypeRegistry::defaultValidation($field['type'] ?? 'text'),
                    'colClass' => $field['colClass'] ?? 'col-12',
                    'help' => $field['help'] ?? null,
                    'placeholder' => $field['placeholder'] ?? null,
                ];
            }

            if ($fields !== []) {
                $forms[] = [
                    'key' => $formKey,
                    'schema' => $schemaName,
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
                'label' => $plugin->pluginDetails()['name'] ?? Str::headline($slug),
                'route' => $this->inferRouteSlug($slug),
                'icon' => 'bi-box',
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
        $studly = Str::studly($slug);
        $modelClass = Str::singular($studly);
        $table = Str::snake(Str::plural($slug));

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
}
