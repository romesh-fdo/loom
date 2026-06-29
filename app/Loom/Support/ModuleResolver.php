<?php

namespace Loom\Support;

use Loom\Features\Contracts\FormModule;
use Loom\Features\FeatureManager;
use Loom\System\PluginBase;
use Loom\System\PluginManager;

class ModuleResolver
{
    public static function resolve(string $moduleId): ?FormModule
    {
        $plugin = app(PluginManager::class)->getPlugin($moduleId);

        if ($plugin instanceof PluginBase) {
            return $plugin;
        }

        $feature = app(FeatureManager::class)->getFeature($moduleId);

        if ($feature instanceof FormModule) {
            return $feature;
        }

        return null;
    }
}
