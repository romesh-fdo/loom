<?php

namespace Loom\Pages;

use Loom\System\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'Pages',
            'description' => 'Manage site pages for Loom CMS',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Pages',
                'url' => route('loom.pages.index'),
                'route' => 'loom.pages.*',
                'icon' => 'bi-file-earmark-text',
                'order' => 150,
                'parent' => [
                    'label' => 'Content',
                    'icon' => 'bi-file-earmark-text-fill',
                    'order' => 100,
                ],
            ],
        ];
    }
}
