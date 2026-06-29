<?php

namespace Loom\Features\PluginBuilder;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Plugin Builder',
            'description' => 'Build and manage Loom plugins visually',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Plugin Builder',
                'url' => route('loom.plugin-builder.index'),
                'route' => 'loom.plugin-builder.*',
                'icon' => 'bi-tools',
                'order' => 200,
                'parent' => [
                    'label' => 'System',
                    'icon' => 'bi-gear-fill',
                    'order' => 200,
                ],
            ],
        ];
    }
}
