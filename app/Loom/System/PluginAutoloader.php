<?php

namespace Loom\System;

use Illuminate\Support\Str;

class PluginAutoloader
{
    /** @var array<string, true> */
    protected static array $coreNamespaces = [
        'Loom\\System\\' => true,
        'Loom\\Support\\' => true,
        'Loom\\Providers\\' => true,
        'Loom\\Features\\' => true,
        'Loom\\Console\\' => true,
        'Loom\\Http\\' => true,
        'Loom\\Builder\\' => true,
    ];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load'], prepend: true);
    }

    public static function load(string $class): void
    {
        if (! str_starts_with($class, 'Loom\\')) {
            return;
        }

        foreach (array_keys(self::$coreNamespaces) as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return;
            }
        }

        $parts = explode('\\', $class);

        if (count($parts) < 2 || $parts[0] !== 'Loom') {
            return;
        }

        $pluginFolder = Str::kebab($parts[1]);
        $basePath = config('loom.plugins_path', base_path('plugins'))."/loom/{$pluginFolder}";

        $className = array_pop($parts);
        $segments = array_slice($parts, 2);

        if ($segments === []) {
            $file = "{$basePath}/{$className}.php";
        } else {
            $directory = $basePath.'/'.implode('/', array_map('strtolower', $segments));
            $file = "{$directory}/{$className}.php";
        }

        if (is_file($file)) {
            require $file;
        }
    }
}
