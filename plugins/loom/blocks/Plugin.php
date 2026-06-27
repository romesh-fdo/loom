<?php

namespace Loom\Blocks;

use Loom\Blocks\Models\Block;
use Loom\System\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'Blocks',
            'description' => 'Manage reusable content blocks for Loom CMS',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Blocks',
                'url' => route('loom.blocks.index'),
                'route' => 'loom.blocks.*',
                'icon' => 'bi-bricks',
                'order' => 100,
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
            'basic-form' => [
                'schema' => 'basic',
            ],
            'configuration-form' => [
                'schema' => 'configuration',
            ],
        ];
    }

    public function getBlockByName(string $name): ?Block
    {
        return Block::where('name', $name)->first();
    }
}
