<?php

namespace Loom\PageBlocks;

use Loom\System\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'Page Blocks',
            'description' => 'Manage Page Blocks for Loom CMS',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Page Blocks',
                'url' => route('loom.page-blocks.index'),
                'route' => 'loom.page-blocks.*',
                'icon' => 'bi-box',
                'order' => 200,
                'parent' => [
                    'label' => 'Content',
                    'icon' => 'bi-file-earmark-text-fill',
                    'order' => 100,
                ],
            ],
        ];
    }

    public function registerForms(): array
    {
        return [
            'basic-form' => ['schema' => 'basic'],
        ];
    }
}
