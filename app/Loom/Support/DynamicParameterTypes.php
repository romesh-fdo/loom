<?php

namespace Loom\Support;

use Illuminate\Support\Str;

class DynamicParameterTypes
{
    /**
     * Partials that are not usable as dynamic block/segment parameters.
     *
     * @var list<string>
     */
    private const EXCLUDED_PARTIALS = [
        'dynamic_code',
        'repeater',
        'block_repeater',
    ];

    /**
     * @return array<string, string> type => label
     */
    public static function labels(): array
    {
        $types = [];

        foreach (glob(resource_path('views/admin/fields/partials/*.blade.php')) ?: [] as $path) {
            $type = basename($path, '.blade.php');

            if (str_starts_with($type, '_')) {
                continue;
            }

            if (in_array($type, self::EXCLUDED_PARTIALS, true)) {
                continue;
            }

            $types[$type] = self::labelFor($type);
        }

        ksort($types);

        return $types;
    }

    /**
     * @return list<string>
     */
    public static function scalarTypes(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [...self::scalarTypes(), 'repeater'];
    }

    private static function labelFor(string $type): string
    {
        return match ($type) {
            'datetime-local' => 'Date & time',
            'richtext' => 'Rich text',
            default => Str::headline(str_replace('-', ' ', $type)),
        };
    }
}
