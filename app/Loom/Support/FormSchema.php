<?php

namespace Loom\Support;

use Loom\System\PluginManager;

class FormSchema
{
    public static function load(
        string $pluginId,
        string $schema,
        ?object $model = null,
        array $persistedFields = [],
        ?array $values = null
    ): array {
        $plugin = app(PluginManager::class)->getPlugin($pluginId);

        if ($plugin === null) {
            return self::emptySchema();
        }

        $raw = self::readRaw($plugin, $schema);

        if ($raw === null) {
            return self::emptySchema();
        }

        if (self::isLegacyFlatSchema($raw)) {
            $raw = self::convertLegacySchema($raw);
        }

        $meta = self::normalizeMeta($raw['meta'] ?? [], $raw);

        if ($persistedFields === []) {
            $persistedFields = $meta['persisted_fields'];
        }

        $fields = [];

        foreach ($raw['fields'] ?? [] as $name => $field) {
            $fields[$name] = self::applyFieldDefaults($name, $field);

            if (in_array($name, $persistedFields, true)) {
                $fieldType = $fields[$name]['type'] ?? $fields[$name]['input'] ?? 'text';

                if ($fieldType === 'repeater') {
                    $default = $values[$name]
                        ?? (is_object($model) ? ($model->{$name} ?? null) : null)
                        ?? ($fields[$name]['value'] ?? []);

                    $fields[$name]['value'] = old($name, is_array($default) ? $default : []);
                } else {
                    $default = $values[$name]
                        ?? (is_object($model) ? ($model->{$name} ?? null) : null)
                        ?? ($fields[$name]['value'] ?? '');

                    $fields[$name]['value'] = old($name, $default);
                }
            }
        }

        return [
            'meta' => $meta,
            'form' => self::applyFormDefaults($raw['form'] ?? []),
            'layout' => $raw['layout'] ?? [],
            'fields' => $fields,
        ];
    }

    public static function meta(string $pluginId, string $schema): array
    {
        $plugin = app(PluginManager::class)->getPlugin($pluginId);

        if ($plugin === null) {
            return self::normalizeMeta([]);
        }

        $raw = self::readRaw($plugin, $schema);

        if ($raw === null) {
            return self::normalizeMeta([]);
        }

        return self::normalizeMeta($raw['meta'] ?? [], $raw);
    }

    protected static function readRaw(object $plugin, string $schema): ?array
    {
        $path = $plugin->getPluginPath()."/schemas/{$schema}.json";

        if (! file_exists($path)) {
            return null;
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    protected static function normalizeMeta(array $meta, ?array $raw = null): array
    {
        $persistedFields = $meta['persisted_fields'] ?? null;

        if ($persistedFields === null && $raw !== null) {
            $persistedFields = self::inferPersistedFields($raw);
        }

        return [
            'label' => $meta['label'] ?? null,
            'description' => $meta['description'] ?? null,
            'order' => (int) ($meta['order'] ?? 500),
            'storage' => $meta['storage'] ?? 'model',
            'persisted_fields' => $persistedFields ?? [],
            'showDemo' => (bool) ($meta['showDemo'] ?? false),
            'layout' => $meta['layout'] ?? 'panel',
        ];
    }

    /**
     * @return list<string>
     */
    protected static function inferPersistedFields(array $raw): array
    {
        $fields = [];

        foreach ($raw['fields'] ?? [] as $name => $field) {
            if (($field['section'] ?? 'block') !== 'demo') {
                $fields[] = $name;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function validationRules(array $schema, ?array $only = null): array
    {
        $rules = [];

        foreach ($schema['fields'] ?? [] as $name => $field) {
            if ($only !== null && ! in_array($name, $only, true)) {
                continue;
            }

            $type = $field['type'] ?? $field['input'] ?? 'text';

            if ($type === 'repeater') {
                if (! empty($field['validation']) && is_array($field['validation'])) {
                    $rules[$name] = $field['validation'];
                }

                foreach ($field['items']['fields'] ?? [] as $subName => $subField) {
                    if (! empty($subField['validation']) && is_array($subField['validation'])) {
                        $rules["{$name}.*.{$subName}"] = $subField['validation'];
                    }
                }

                continue;
            }

            if (! empty($field['validation']) && is_array($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }

        return $rules;
    }

    public static function loadForDefinition(string $pluginId, array $definition, ?object $model = null): array
    {
        $schemaName = $definition['schema'] ?? '';
        $meta = self::meta($pluginId, $schemaName);
        $values = $meta['storage'] === 'config' && is_object($model)
            ? (array) ($model->config ?? [])
            : null;

        return self::load(
            $pluginId,
            $schemaName,
            $model,
            $meta['persisted_fields'],
            $values
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $formDefinitions
     * @return array<string, list<string>>
     */
    public static function validationRulesForDefinitions(string $pluginId, array $formDefinitions): array
    {
        $rules = [];

        foreach ($formDefinitions as $definition) {
            $schema = self::loadForDefinition($pluginId, $definition, null);
            $rules = array_merge(
                $rules,
                self::validationRules($schema, $schema['meta']['persisted_fields'] ?? [])
            );
        }

        return $rules;
    }

    /**
     * @param  array<string, array<string, mixed>>  $formDefinitions
     * @return array<string, mixed>
     */
    public static function mapValidatedToModel(array $validated, array $formDefinitions, string $pluginId): array
    {
        $attributes = ['config' => []];

        foreach ($formDefinitions as $definition) {
            $meta = self::meta($pluginId, $definition['schema'] ?? '');

            foreach ($meta['persisted_fields'] as $field) {
                if (! array_key_exists($field, $validated)) {
                    continue;
                }

                if ($meta['storage'] === 'config') {
                    $attributes['config'][$field] = $validated[$field];
                } else {
                    $attributes[$field] = $validated[$field];
                }
            }
        }

        return $attributes;
    }

    protected static function emptySchema(): array
    {
        return [
            'meta' => self::normalizeMeta([]),
            'form' => self::applyFormDefaults([]),
            'layout' => [],
            'fields' => [],
        ];
    }

    protected static function isLegacyFlatSchema(array $raw): bool
    {
        return ! isset($raw['fields']) && ! isset($raw['form']) && ! isset($raw['layout']);
    }

    protected static function convertLegacySchema(array $raw): array
    {
        $fields = [];
        $layout = [];

        foreach ($raw as $name => $field) {
            if (! is_array($field)) {
                continue;
            }

            $fields[$name] = $field;
            $layout[] = [
                'section' => $field['section'] ?? 'block',
                'rowClass' => $field['rowClass'] ?? 'row g-3 mb-3',
                'fields' => [
                    [
                        'name' => $name,
                        'colClass' => $field['colClass'] ?? 'col-12',
                    ],
                ],
            ];
        }

        return [
            'form' => [],
            'layout' => $layout,
            'fields' => $fields,
        ];
    }

    protected static function applyFormDefaults(array $form): array
    {
        return array_filter([
            'id' => $form['id'] ?? null,
            'class' => $form['class'] ?? null,
            'wrapperClass' => $form['wrapperClass'] ?? null,
            'method' => $form['method'] ?? 'POST',
            'enctype' => $form['enctype'] ?? 'multipart/form-data',
            'attributes' => $form['attributes'] ?? [],
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected static function applyFieldDefaults(string $name, array $field): array
    {
        $type = $field['type'] ?? $field['input'] ?? 'text';

        $field['id'] = $field['id'] ?? 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
        $field['wrapperClass'] = $field['wrapperClass'] ?? 'mb-0';
        $field['colClass'] = $field['colClass'] ?? 'col-12';
        $field['labelClass'] = $field['labelClass'] ?? match ($type) {
            'checkbox' => 'form-check-label',
            default => 'form-label',
        };

        if (! isset($field['class'])) {
            $field['class'] = match ($type) {
                'select' => 'form-select',
                'checkbox', 'radio' => 'form-check-input',
                'code' => 'form-control d-none',
                'color' => 'form-control form-control-color',
                default => 'form-control',
            };
        }

        if ($type === 'repeater') {
            $field['items'] = self::normalizeRepeaterItems($field['items'] ?? []);
        }

        return $field;
    }

    protected static function normalizeRepeaterItems(array $items): array
    {
        $fields = [];

        foreach ($items['fields'] ?? [] as $subName => $subField) {
            $fields[$subName] = self::applyFieldDefaults($subName, $subField);
        }

        return [
            'layout' => $items['layout'] ?? [],
            'fields' => $fields,
        ];
    }
}
