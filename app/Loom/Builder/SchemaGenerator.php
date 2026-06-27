<?php

namespace Loom\Builder;

use Illuminate\Support\Str;

class SchemaGenerator
{
    /**
     * @return array<string, string> relative path => JSON content
     */
    public function generate(Blueprint $blueprint): array
    {
        $modelFields = $blueprint->modelFields();

        if ($modelFields === []) {
            return [];
        }

        return [
            'schemas/basic.json' => $this->encode($this->buildBasicSchema($blueprint, $modelFields)),
        ];
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
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    protected function fieldDefinition(array $field, string $section): array
    {
        $type = $field['type'] ?? 'text';
        $name = $field['name'];
        [$validation, $validationMessages] = $this->resolveValidation($field, $type);

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

        if ($validationMessages !== []) {
            $definition['validation_messages'] = $validationMessages;
        }

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

    /**
     * @param  array<string, mixed>  $field
     * @return array{0: list<string>, 1: array<string, string>}
     */
    protected function resolveValidation(array $field, string $type): array
    {
        $validation = [];
        $validationMessages = [];

        foreach ($field['validation_rules'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $rule = trim((string) ($entry['rule'] ?? ''));

            if ($rule === '') {
                continue;
            }

            $validation[] = $rule;

            $message = trim((string) ($entry['message'] ?? ''));

            if ($message !== '') {
                $validationMessages[Str::before($rule, ':')] = $message;
            }
        }

        if ($validation === []) {
            $validation = array_values(array_filter(
                $field['validation'] ?? [],
                fn ($rule) => is_string($rule) && trim($rule) !== ''
            ));

            if ($validation === []) {
                $validation = FieldTypeRegistry::defaultValidation($type);
            }

            $existingMessages = $field['validation_messages'] ?? [];

            if (is_array($existingMessages)) {
                foreach ($existingMessages as $key => $message) {
                    if (is_string($key) && is_string($message) && $message !== '') {
                        $validationMessages[$key] = $message;
                    }
                }
            }
        }

        return [$validation, $validationMessages];
    }

    protected function encode(array $schema): string
    {
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }
}
