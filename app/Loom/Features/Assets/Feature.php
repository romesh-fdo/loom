<?php

namespace Loom\Features\Assets;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Assets',
            'description' => 'Manage project CSS, JavaScript, images, and other static files',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Assets',
                'url' => route('loom.assets.index'),
                'route' => 'loom.assets.*',
                'icon' => 'bi-folder2-open',
                'order' => 125,
                'parent' => [
                    'label' => 'Content',
                    'icon' => 'bi-file-earmark-text-fill',
                    'order' => 100,
                ],
            ],
        ];
    }
}
