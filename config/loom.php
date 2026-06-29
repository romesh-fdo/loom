<?php

return [
    'name' => env('LOOM_NAME', 'Loom'),

    'plugins_path' => base_path('plugins'),

    // Use 'auto' to discover all plugins under plugins/*/* — no manual list needed.
    // Or pass an explicit array to allow-list specific plugins.
    'plugins' => env('LOOM_PLUGINS', 'auto'),

    // Skip plugins without removing them from disk.
    'disabled_plugins' => [],

    // Cached plugin list written by: php artisan loom:cache
    'plugins_cache' => base_path('bootstrap/cache/loom-plugins.php'),

    'admin' => [
        'route_prefix' => 'admin',
        'route_name_prefix' => 'loom.',
    ],

    'table_prefix' => env('LOOM_TABLE_PREFIX', 'loom_'),

    'active_theme' => env('LOOM_ACTIVE_THEME', 'default'),

    'assets' => [
        'public_path' => 'theme/'.env('LOOM_ACTIVE_THEME', 'default').'/assets',
    ],

    'media' => [
        'public_path' => 'media',
    ],
];
