<?php

namespace Loom\Features\Blocks;

use Loom\Features\Blocks\Models\Block;
use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
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
