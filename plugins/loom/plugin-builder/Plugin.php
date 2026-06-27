<?php

namespace Loom\PluginBuilder;

use Loom\System\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
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
                'order' => 100,
            ],
        ];
    }
}
