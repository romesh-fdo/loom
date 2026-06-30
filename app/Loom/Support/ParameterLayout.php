<?php

namespace Loom\Support;

class ParameterLayout
{
    /**
     * @return list<string>
     */
    public static function allowedColClasses(): array
    {
        return [
            'col-12',
            'col-md-3',
            'col-md-4',
            'col-md-6',
            'col-md-8',
            'col-md-9',
        ];
    }

    public static function defaultColClass(string $type): string
    {
        return in_array($type, ['repeater', 'textarea', 'richtext', 'code', 'media_selector', 'media_attach', 'media_finder'], true)
            ? 'col-12'
            : 'col-md-6';
    }

    public static function isValidColClass(string $colClass): bool
    {
        return in_array($colClass, self::allowedColClasses(), true);
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @return array<int, list<array<string, mixed>>>
     */
    public static function groupByRow(array $parameters): array
    {
        $groups = [];

        foreach ($parameters as $parameter) {
            $row = max(1, (int) ($parameter['row'] ?? 1));
            $groups[$row][] = $parameter;
        }

        ksort($groups);

        return $groups;
    }
}
