<?php

namespace Loom\Builder;

class BootstrapIconRegistry
{
    /**
     * @return list<array{name: string, label: string}>
     */
    public static function all(): array
    {
        static $icons = null;

        if ($icons !== null) {
            return $icons;
        }

        $path = resource_path('data/bootstrap-icons.json');

        if (! file_exists($path)) {
            return $icons = [];
        }

        $decoded = json_decode(file_get_contents($path), true);

        if (! is_array($decoded)) {
            return $icons = [];
        }

        return $icons = array_values(array_filter($decoded, function ($icon) {
            return is_array($icon)
                && is_string($icon['name'] ?? null)
                && is_string($icon['label'] ?? null);
        }));
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    public static function search(?string $query, int $limit = 80): array
    {
        $icons = self::all();

        if ($query === null || trim($query) === '') {
            return array_slice($icons, 0, $limit);
        }

        $needle = strtolower(trim($query));

        return array_values(array_slice(array_filter($icons, function (array $icon) use ($needle) {
            return str_contains(strtolower($icon['name']), $needle)
                || str_contains(strtolower($icon['label']), $needle);
        }), 0, $limit));
    }

    public static function isValid(string $icon): bool
    {
        foreach (self::all() as $entry) {
            if ($entry['name'] === $icon) {
                return true;
            }
        }

        return false;
    }
}
