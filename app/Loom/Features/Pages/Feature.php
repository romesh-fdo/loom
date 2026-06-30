<?php

namespace Loom\Features\Pages;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
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
        ];
    }
}
