<?php

namespace Loom\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PluginModelFunctions
{
    public const HANDLER_PREFIX = '__model__:';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitionsFor(PluginBase $plugin): array
    {
        $modelClass = self::resolveModelClass($plugin);

        if ($modelClass === null) {
            return [];
        }

        $returns = self::returnFieldsFor($plugin, $modelClass);
        $definitions = [
            'getById' => [
                'label' => 'Get by ID',
                'handler' => self::HANDLER_PREFIX.'getById',
                'builtin' => true,
                'parameters' => [
                    [
                        'name' => 'id',
                        'label' => 'ID',
                        'type' => 'number',
                        'dynamic' => true,
                    ],
                ],
                'returns' => $returns,
            ],
            'getFirst' => [
                'label' => 'Get first record',
                'handler' => self::HANDLER_PREFIX.'getFirst',
                'builtin' => true,
                'parameters' => [],
                'returns' => $returns,
            ],
        ];

        foreach (self::modelAttributeNames($plugin, $modelClass) as $attribute) {
            if ($attribute === 'id') {
                continue;
            }

            $functionKey = 'getBy'.Str::studly($attribute);

            $definitions[$functionKey] = [
                'label' => 'Get by '.Str::headline($attribute),
                'handler' => self::HANDLER_PREFIX.'getByField',
                'builtin' => true,
                'model_field' => $attribute,
                'parameters' => [
                    [
                        'name' => $attribute,
                        'label' => Str::headline($attribute),
                        'type' => 'text',
                        'dynamic' => true,
                    ],
                ],
                'returns' => $returns,
            ];
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $arguments
     */
    public static function call(PluginBase $plugin, string $method, array $definition, array $arguments): mixed
    {
        $modelClass = self::resolveModelClass($plugin);

        if ($modelClass === null) {
            return null;
        }

        try {
            return match ($method) {
                'getById' => self::getById($modelClass, $arguments),
                'getFirst' => $modelClass::query()->first(),
                'getByField' => self::getByField($modelClass, $definition, $arguments),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    public static function resolveModelClass(PluginBase $plugin): ?string
    {
        $manifest = self::readManifest($plugin);
        $studlyPlugin = Str::studly($plugin->getName());
        $modelShort = (string) ($manifest['model'] ?? Str::singular($studlyPlugin));
        $class = "Loom\\{$studlyPlugin}\\Models\\{$modelShort}";

        if (! class_exists($class)) {
            return null;
        }

        if (! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<array{name: string, label: string}>
     */
    public static function returnFieldsFor(PluginBase $plugin, string $modelClass): array
    {
        $fields = [
            ['name' => 'id', 'label' => 'ID'],
        ];

        foreach (self::modelAttributeNames($plugin, $modelClass) as $attribute) {
            if ($attribute === 'id') {
                continue;
            }

            $fields[] = [
                'name' => $attribute,
                'label' => Str::headline($attribute),
            ];
        }

        $fields[] = ['name' => 'created_at', 'label' => 'Created at'];
        $fields[] = ['name' => 'updated_at', 'label' => 'Updated at'];

        return $fields;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<string>
     */
    protected static function modelAttributeNames(PluginBase $plugin, string $modelClass): array
    {
        $attributes = [];

        if (method_exists($modelClass, 'loomFillable')) {
            $attributes = array_values(array_filter(
                $modelClass::loomFillable(),
                fn ($name) => is_string($name) && $name !== ''
            ));
        }

        if ($attributes !== []) {
            return $attributes;
        }

        try {
            $instance = new $modelClass;
            $attributes = array_values(array_filter(
                $instance->getFillable(),
                fn ($name) => is_string($name) && $name !== ''
            ));
        } catch (\Throwable) {
            $attributes = [];
        }

        if ($attributes !== []) {
            return $attributes;
        }

        return self::attributesFromFormSchema($plugin);
    }

    /**
     * @return list<string>
     */
    protected static function attributesFromFormSchema(PluginBase $plugin): array
    {
        $schemaPath = $plugin->getPluginPath().'/schemas/basic.json';

        if (! is_file($schemaPath)) {
            return [];
        }

        $schema = json_decode((string) file_get_contents($schemaPath), true);

        if (! is_array($schema)) {
            return [];
        }

        $attributes = [];

        foreach ($schema['fields'] ?? [] as $name => $field) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            if (is_array($field) && ($field['section'] ?? 'block') === 'demo') {
                continue;
            }

            $attributes[] = $name;
        }

        return $attributes;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $arguments
     */
    protected static function getById(string $modelClass, array $arguments): mixed
    {
        $id = $arguments['id'] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        return $modelClass::query()->find($id);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $arguments
     */
    protected static function getByField(string $modelClass, array $definition, array $arguments): mixed
    {
        $column = (string) ($definition['model_field'] ?? '');

        if ($column === '') {
            return null;
        }

        $value = $arguments[$column] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return $modelClass::query()->where($column, $value)->first();
    }

    /**
     * @return array<string, string>
     */
    protected static function readManifest(PluginBase $plugin): array
    {
        $manifestPath = $plugin->getPluginPath().'/plugin.yaml';

        if (! is_file($manifestPath)) {
            return [];
        }

        $manifest = [];

        foreach (file($manifestPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $manifest[$key] = trim($value, " \t\"'");
        }

        return $manifest;
    }
}
