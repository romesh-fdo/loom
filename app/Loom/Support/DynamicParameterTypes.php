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
        'media_parameter',
        'url_parameter',
    ];

    /**
     * Types only available via dedicated context-menu actions, not the text modal dropdown.
     *
     * @var list<string>
     */
    private const MEDIA_MENU_TYPES = [
        'media_selector',
        'media_attach',
        'media_finder',
    ];

    /**
     * Non-text field types opened from the context menu instead of the text modal dropdown.
     *
     * @var list<string>
     */
    private const CONTEXT_MENU_FIELD_TYPES = [
        'file',
        'select',
        'radio',
        'checkbox',
    ];

    /**
     * Field types that require a list of value/label options.
     *
     * @var list<string>
     */
    private const OPTION_FIELD_TYPES = [
        'select',
        'radio',
    ];

    /**
     * @return array<string, string> type => label
     */
    public static function labels(): array
    {
        $types = self::discoveredPartialTypes();
        $types = array_merge($types, self::extraTypeLabels());

        ksort($types);

        return $types;
    }

    /**
     * Types shown in the "Make dynamic text" modal dropdown.
     *
     * @return array<string, string>
     */
    public static function textModalLabels(): array
    {
        return array_diff_key(self::labels(), array_flip([
            ...self::MEDIA_MENU_TYPES,
            ...self::CONTEXT_MENU_FIELD_TYPES,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function optionTypes(): array
    {
        return self::OPTION_FIELD_TYPES;
    }

    /**
     * @return list<string>
     */
    public static function contextMenuFieldTypes(): array
    {
        return [
            ...self::CONTEXT_MENU_FIELD_TYPES,
            ...self::MEDIA_MENU_TYPES,
        ];
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

    /**
     * @return array<string, string>
     */
    private static function discoveredPartialTypes(): array
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

        return $types;
    }

    /**
     * @return array<string, string>
     */
    private static function extraTypeLabels(): array
    {
        return [
            'media_selector' => 'Media selector',
            'media_attach' => 'Media attach',
        ];
    }

    private static function labelFor(string $type): string
    {
        return match ($type) {
            'datetime-local' => 'Date & time',
            'richtext' => 'Rich text',
            'media_finder' => 'Media finder',
            'url' => 'URL',
            default => Str::headline(str_replace('-', ' ', $type)),
        };
    }
}
