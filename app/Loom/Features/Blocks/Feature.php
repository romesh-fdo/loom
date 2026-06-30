<?php

namespace Loom\Features\Blocks;

use Loom\Features\FeatureBase;
use Loom\Support\ThemeContent\BlockStore;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeManager;

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
                'order' => 110,
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

    public function getBlockByName(string $name): ?ThemeFileRecord
    {
        $themeSlug = app(ThemeManager::class)->activeSlug();

        return app(BlockStore::class)->all($themeSlug)
            ->first(fn (ThemeFileRecord $block) => ($block->name ?? '') === $name);
    }
}
