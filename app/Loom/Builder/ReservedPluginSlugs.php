<?php

namespace Loom\Builder;

class ReservedPluginSlugs
{
    /** @var list<string> */
    public const SLUGS = [
        'plugin-builder',
        'blocks',
        'pages',
        'page-blocks',
        'assets',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower(trim($slug)), self::SLUGS, true);
    }
}
