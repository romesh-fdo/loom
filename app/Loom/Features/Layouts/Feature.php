<?php

namespace Loom\Features\Layouts;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Layouts',
            'description' => 'Manage HTML layout templates for Loom CMS',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Layouts',
                'url' => route('loom.layouts.index'),
                'route' => 'loom.layouts.*',
                'icon' => 'bi-layout-text-window',
                'order' => 115,
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
