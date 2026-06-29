<?php

namespace Loom\Features;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Loom\System\PluginManager;

class FeatureManager
{
    /** @var array<string, FeatureBase> */
    protected array $features = [];

    protected bool $registered = false;

    protected bool $booted = false;

    /**
     * @return array<class-string<FeatureBase>, string>
     */
    protected function featureMap(): array
    {
        return [
            \Loom\Features\PluginBuilder\Feature::class => 'loom.plugin-builder',
            \Loom\Features\Blocks\Feature::class => 'loom.blocks',
            \Loom\Features\Pages\Feature::class => 'loom.pages',
            \Loom\Features\Segments\Feature::class => 'loom.segments',
            \Loom\Features\Media\Feature::class => 'loom.media',
            \Loom\Features\Assets\Feature::class => 'loom.assets',
            \Loom\Features\Theme\Feature::class => 'loom.theme',
        ];
    }

    public function registerAll(): void
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->featureMap() as $class => $identifier) {
            $path = $this->resolveFeaturePath($class);

            if (! is_dir($path)) {
                continue;
            }

            /** @var FeatureBase $feature */
            $feature = new $class($identifier, $path);
            $this->features[$identifier] = $feature;
            $this->registerFeature($feature);
            $feature->register();
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

        foreach ($this->features as $feature) {
            $feature->boot();
        }

        $this->booted = true;
    }

    protected function resolveFeaturePath(string $class): string
    {
        $relative = Str::after($class, 'Loom\\Features\\');
        $featureName = Str::before($relative, '\\');

        return app_path("Loom/Features/{$featureName}");
    }

    protected function registerFeature(FeatureBase $feature): void
    {
        $path = $feature->getFeaturePath();

        if (is_dir($path.'/views')) {
            View::addNamespace($feature->getViewNamespace(), $path.'/views');
        }

        $routesFile = $path.'/routes.php';

        if (file_exists($routesFile)) {
            Route::middleware('web')->group($routesFile);
        }
    }

    public function getFeature(string $identifier): ?FeatureBase
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        return $this->features[$identifier] ?? null;
    }

    /**
     * @return array<string, FeatureBase>
     */
    public function getFeatures(): array
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        return $this->features;
    }

    public function getNavigation(): array
    {
        if (! $this->registered) {
            $this->registerAll();
        }

        return app(PluginManager::class)->buildNavigationFromItems(
            $this->collectNavigationItems()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function collectNavigationItems(): array
    {
        $items = [];

        foreach ($this->features as $feature) {
            foreach ($feature->registerNavigation() as $item) {
                if (is_array($item)) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    public function getMergedNavigation(): array
    {
        $pluginManager = app(PluginManager::class);

        $this->registerAll();
        $pluginManager->registerAll();

        $items = array_merge(
            $this->collectNavigationItems(),
            $pluginManager->collectNavigationItems()
        );

        return $pluginManager->buildNavigationFromItems($items);
    }
}
