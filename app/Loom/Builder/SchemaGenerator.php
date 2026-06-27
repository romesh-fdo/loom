<?php

namespace Loom\Builder;

class SchemaGenerator
{
    /**
     * @return array<string, string> relative path => JSON content
     */
    public function generate(Blueprint $blueprint): array
    {
        $files = [];
        $modelFields = $blueprint->modelFields();
        $configFields = $blueprint->configFields();

        if ($modelFields !== []) {
            $files['schemas/basic.json'] = $this->encode($this->buildBasicSchema($blueprint, $modelFields));
        }

        if ($configFields !== []) {
            $files['schemas/configuration.json'] = $this->encode($this->buildConfigurationSchema($blueprint, $configFields));
        }

        return $files;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function buildBasicSchema(Blueprint $blueprint, array $fields): array
    {
        $fieldDefs = [];
        $layoutRows = [];
        $persisted = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $persisted[] = $name;

            $fieldDefs[$name] = $this->fieldDefinition($field, 'block');
            $layoutRows[] = [
                'section' => 'block',
                'rowClass' => 'row g-3 mb-3',
                'fields' => [
                    ['name' => $name, 'colClass' => $field['colClass'] ?? 'col-12'],
                ],
            ];
        }

        return [
            'form' => [
                'id' => 'loom-'.$blueprint->pluginSlug().'-form',
                'class' => 'loom-'.$blueprint->pluginSlug().'-form',
                'method' => 'POST',
                'enctype' => 'multipart/form-data',
                'attributes' => [
                    'data-loom-form' => $blueprint->pluginSlug().'-basic',
                    'novalidate' => false,
                ],
            ],
            'layout' => $layoutRows,
            'fields' => $fieldDefs,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function buildConfigurationSchema(Blueprint $blueprint, array $fields): array
    {
        $fieldDefs = [];
        $layoutFields = [];
        $persisted = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $persisted[] = $name;
            $fieldDefs[$name] = $this->fieldDefinition($field, 'configuration');
            $layoutFields[] = ['name' => $name, 'colClass' => $field['colClass'] ?? 'col-md-4'];
        }

        $rows = array_map(fn (array $chunk) => [
            'section' => 'configuration',
            'rowClass' => 'row g-3 mb-3',
            'fields' => $chunk,
        ], array_chunk($layoutFields, 3));

        return [
            'meta' => [
                'label' => $blueprint->pluginLabel().' configuration',
                'description' => 'Settings for this '.$blueprint->pluginLabel(),
                'order' => 5,
                'layout' => 'modal',
                'storage' => 'config',
                'persisted_fields' => $persisted,
            ],
            'form' => [
                'id' => 'loom-'.$blueprint->pluginSlug().'-configuration-form',
                'class' => 'loom-'.$blueprint->pluginSlug().'-configuration-form',
                'attributes' => [
                    'data-loom-form-panel' => 'configuration',
                ],
            ],
            'layout' => $rows,
            'fields' => $fieldDefs,
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    protected function fieldDefinition(array $field, string $section): array
    {
        $type = $field['type'] ?? 'text';
        $name = $field['name'];
        $validation = $field['validation'] ?? FieldTypeRegistry::defaultValidation($type);

        $definition = [
            'section' => $section,
            'type' => $type,
            'label' => $field['label'] ?? ucfirst(str_replace('_', ' ', $name)),
            'id' => 'field-'.str_replace('_', '-', $name),
            'wrapperClass' => 'mb-0',
            'colClass' => $field['colClass'] ?? 'col-12',
            'labelClass' => 'form-label',
            'required' => in_array('required', $validation, true),
            'disabled' => false,
            'readonly' => false,
            'validation' => $validation,
        ];

        $definition['class'] = match ($type) {
            'select' => 'form-select',
            'checkbox' => 'form-check-input',
            'dynamic_code' => 'd-none',
            'code' => 'form-control d-none',
            'color' => 'form-control form-control-color',
            default => 'form-control',
        };

        if ($type === 'dynamic_code') {
            $definition['attributes'] = [
                'data-language' => 'html',
                'spellcheck' => 'false',
            ];
            $definition['help'] = 'HTML template. Highlight text, right-click, and choose Make dynamic.';
        }

        if (! empty($field['placeholder'])) {
            $definition['placeholder'] = $field['placeholder'];
        }

        if (! empty($field['help'])) {
            $definition['help'] = $field['help'];
        }

        return $definition;
    }

    protected function encode(array $schema): string
    {
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }
}
