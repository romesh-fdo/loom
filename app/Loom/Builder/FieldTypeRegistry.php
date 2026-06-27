<?php

namespace Loom\Builder;

class FieldTypeRegistry
{
    /**
     * @return array<string, array{label: string, sql: string, cast: ?string, default_validation: list<string>}>
     */
    public static function types(): array
    {
        return [
            'text' => [
                'label' => 'Text',
                'sql' => 'string',
                'cast' => null,
                'default_validation' => ['nullable', 'string', 'max:255'],
            ],
            'email' => [
                'label' => 'Email',
                'sql' => 'string',
                'cast' => null,
                'default_validation' => ['nullable', 'email', 'max:255'],
            ],
            'textarea' => [
                'label' => 'Textarea',
                'sql' => 'text',
                'cast' => null,
                'default_validation' => ['nullable', 'string'],
            ],
            'number' => [
                'label' => 'Number',
                'sql' => 'integer',
                'cast' => 'integer',
                'default_validation' => ['nullable', 'integer'],
            ],
            'checkbox' => [
                'label' => 'Checkbox',
                'sql' => 'boolean',
                'cast' => 'boolean',
                'default_validation' => ['nullable', 'boolean'],
            ],
            'select' => [
                'label' => 'Select',
                'sql' => 'string',
                'cast' => null,
                'default_validation' => ['nullable', 'string', 'max:255'],
            ],
            'color' => [
                'label' => 'Color',
                'sql' => 'string',
                'cast' => null,
                'default_validation' => ['nullable', 'string', 'max:32'],
            ],
            'dynamic_code' => [
                'label' => 'Dynamic code',
                'sql' => 'json',
                'cast' => 'array',
                'default_validation' => ['required', 'json'],
            ],
            'file' => [
                'label' => 'File',
                'sql' => 'string',
                'cast' => null,
                'default_validation' => ['nullable', 'string', 'max:255'],
            ],
        ];
    }

    public static function labels(): array
    {
        return collect(self::types())->mapWithKeys(
            fn (array $type, string $key) => [$key => $type['label']]
        )->all();
    }

    public static function hasType(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    public static function sqlType(string $type): string
    {
        return self::types()[$type]['sql'] ?? 'string';
    }

    public static function cast(string $type): ?string
    {
        return self::types()[$type]['cast'] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function defaultValidation(string $type): array
    {
        return self::types()[$type]['default_validation'] ?? ['nullable', 'string'];
    }

    /**
     * @return list<string>
     */
    public static function validationPresets(): array
    {
        return [
            'required',
            'nullable',
            'string',
            'email',
            'json',
            'boolean',
            'integer',
            'max:255',
            'max:1000',
        ];
    }
}
