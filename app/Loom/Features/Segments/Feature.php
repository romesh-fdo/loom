<?php

namespace Loom\Features\Segments;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Segments',
            'description' => 'Manage layout segments like header, footer, and scroll-to-top',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Segments',
                'url' => route('loom.segments.index'),
                'route' => 'loom.segments.*',
                'icon' => 'bi-layout-split',
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
        ];
    }
}
