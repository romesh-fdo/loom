<?php

namespace Loom\System;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class PluginManager
{
    /** @var array<string, PluginBase> */
    protected array $plugins = [];

    protected bool $registered = false;

    protected bool $booted = false;

    public function registerAll(): void
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->discoverPlugins() as $identifier => $plugin) {
            $this->plugins[$identifier] = $plugin;
            $this->registerPlugin($plugin);
            $plugin->register();
        }

        $this->registered = true;
    }

    public function bootAll(): void
    {
        if ($this->booted) {
            return;
        }

        if (! $this->registered) {
            $this->registerAll();
        }

        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }

        $this->booted = true;
    }

    /**
     * @return array<string, PluginBase>
     */
    protected function discoverPlugins(): array
    {
        $plugins = [];

        foreach ($this->getEnabledPluginIdentifiers() as $identifier) {
            $plugin = $this->resolvePlugin($identifier);

            if ($plugin !== null) {
                $plugins[$identifier] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * @return list<string>
     */
    public function getEnabledPluginIdentifiers(): array
    {
        $configured = config('loom.plugins');

        if (is_array($configured) && $configured !== []) {
            $identifiers = $configured;
        } else {
            $identifiers = $this->getCachedOrScanPluginIdentifiers();
        }

        $disabled = config('loom.disabled_plugins', []);

        return array_values(array_diff($identifiers, $disabled));
    }

    /**
     * @return list<string>
     */
    protected function getCachedOrScanPluginIdentifiers(): array
    {
        $cachePath = $this->getCachePath();

        if (is_file($cachePath)) {
            $cached = require $cachePath;

            if (is_array($cached)) {
                return $cached;
            }
        }

        return $this->scanPluginDirectories();
    }

    /**
     * @return list<string>
     */
    public function scanPluginDirectories(): array
    {
        $plugins = [];
        $basePath = config('loom.plugins_path', base_path('plugins'));

        if (! is_dir($basePath)) {
            return $plugins;
        }

        foreach (glob("{$basePath}/*/*", GLOB_ONLYDIR) ?: [] as $directory) {
            if (! is_file("{$directory}/Plugin.php")) {
                continue;
            }

            $vendor = basename(dirname($directory));
            $name = basename($directory);
            $plugins[] = "{$vendor}.{$name}";
        }

        sort($plugins);

        return $plugins;
    }

    public function cachePluginList(): string
    {
        $path = $this->getCachePath();
        $plugins = $this->scanPluginDirectories();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, '<?php return '.var_export($plugins, true).';'.PHP_EOL);

        return $path;
    }

    public function clearPluginCache(): bool
    {
        $path = $this->getCachePath();

        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    protected function getCachePath(): string
    {
        return config('loom.plugins_cache', base_path('bootstrap/cache/loom-plugins.php'));
    }

    protected function resolvePlugin(string $identifier): ?PluginBase
    {
        if (! str_contains($identifier, '.')) {
            return null;
        }

        [$vendor, $name] = explode('.', $identifier, 2);
        $path = config('loom.plugins_path')."/{$vendor}/{$name}";
        $class = 'Loom\\'.Str::studly($name).'\\Plugin';

        if (! class_exists($class) || ! is_subclass_of($class, PluginBase::class)) {
            return null;
        }

        return new $class($vendor, $name, $path);
    }

    protected function registerPlugin(PluginBase $plugin): void
    {
        $path = $plugin->getPluginPath();

        if (is_dir($path.'/views')) {
            View::addNamespace($plugin->getViewNamespace(), $path.'/views');
        }

        if (is_dir($path.'/updates')) {
            $this->loadMigrationsFrom($path.'/updates');
        }

        $routesFile = $path.'/routes.php';

        if (file_exists($routesFile)) {
            Route::middleware('web')->group($routesFile);
        }
    }

    protected function loadMigrationsFrom(string $path): void
    {
        app()->afterResolving('migrator', function ($migrator) use ($path) {
            $migrator->path($path);
        });
    }

    public function getPlugin(string $identifier): ?PluginBase
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        return $this->plugins[$identifier] ?? null;
    }

    /**
     * @return array<string, PluginBase>
     */
    public function getPlugins(): array
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        return $this->plugins;
    }

    public function call(string $identifier, string $method, mixed ...$arguments): mixed
    {
        $plugin = $this->getPlugin($identifier);

        if ($plugin === null || ! method_exists($plugin, $method)) {
            return null;
        }

        return $plugin->{$method}(...$arguments);
    }

    public function getNavigation(): array
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        $navigation = [];

        foreach ($this->plugins as $plugin) {
            foreach ($plugin->registerNavigation() as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (isset($item['parent']) && is_array($item['parent'])) {
                    $this->addChildNavigationItem($navigation, $item);
                } elseif (isset($item['sideMenu'])) {
                    $this->addLegacyNavigationItem($navigation, $item);
                } else {
                    $this->addTopLevelNavigationItem($navigation, $item);
                }
            }
        }

        uasort($navigation, fn (array $a, array $b) => ($a['order'] ?? 500) <=> ($b['order'] ?? 500));

        $this->sortSideMenus($navigation);

        return $navigation;
    }

    protected function sortSideMenus(array &$navigation): void
    {
        foreach ($navigation as &$item) {
            if (empty($item['sideMenu'])) {
                continue;
            }

            uasort(
                $item['sideMenu'],
                fn (array $a, array $b) => ($a['order'] ?? 500) <=> ($b['order'] ?? 500)
            );
        }
    }

    protected function resolveParentOrder(array $item): int
    {
        return (int) ($item['parent']['order'] ?? $item['order'] ?? 500);
    }

    protected function addChildNavigationItem(array &$navigation, array $item): void
    {
        $parentKey = $this->parentNavigationKey($item['parent']);
        $childKey = $item['key'] ?? Str::slug(strtolower((string) ($item['label'] ?? 'item')), '_');

        if (! isset($navigation[$parentKey])) {
            $navigation[$parentKey] = [
                'label' => $item['parent']['label'] ?? '',
                'icon' => $item['parent']['icon'] ?? '',
                'order' => $this->resolveParentOrder($item),
                'sideMenu' => [],
            ];
        } else {
            $navigation[$parentKey]['order'] = min(
                $navigation[$parentKey]['order'] ?? 500,
                $this->resolveParentOrder($item)
            );
        }

        $navigation[$parentKey]['sideMenu'][$childKey] = $this->normalizeNavItem($item);
    }

    protected function addLegacyNavigationItem(array &$navigation, array $item): void
    {
        $parentKey = $this->parentNavigationKey($item);

        $navigation[$parentKey] = isset($navigation[$parentKey])
            ? $this->mergeNavigationItem($navigation[$parentKey], $item)
            : $item;
    }

    protected function addTopLevelNavigationItem(array &$navigation, array $item): void
    {
        $key = $this->parentNavigationKey($item);

        $navigation[$key] = $this->normalizeNavItem($item);
        $navigation[$key]['order'] = $item['order'] ?? 500;
    }

    protected function normalizeNavItem(array $item): array
    {
        return array_filter([
            'label' => $item['label'] ?? null,
            'url' => $item['url'] ?? null,
            'route' => $item['route'] ?? null,
            'icon' => $item['icon'] ?? null,
            'order' => $item['order'] ?? null,
        ], fn ($value) => $value !== null);
    }

    protected function parentNavigationKey(array $item): string
    {
        $label = strtolower(trim((string) ($item['label'] ?? '')));
        $icon = strtolower(trim((string) ($item['icon'] ?? '')));

        return "{$label}|{$icon}";
    }

    protected function mergeNavigationItem(array $existing, array $incoming): array
    {
        $merged = array_merge($existing, $incoming);
        $merged['label'] = $existing['label'] ?? $incoming['label'] ?? null;
        $merged['icon'] = $existing['icon'] ?? $incoming['icon'] ?? null;
        $merged['order'] = min($existing['order'] ?? 500, $incoming['order'] ?? 500);

        if (isset($existing['sideMenu']) || isset($incoming['sideMenu'])) {
            $merged['sideMenu'] = array_merge(
                $existing['sideMenu'] ?? [],
                $incoming['sideMenu'] ?? []
            );

            uasort(
                $merged['sideMenu'],
                fn (array $a, array $b) => ($a['order'] ?? 500) <=> ($b['order'] ?? 500)
            );
        }

        return $merged;
    }
}
