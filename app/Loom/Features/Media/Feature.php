<?php

namespace Loom\Features\Media;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Media',
            'description' => 'Manage CMS media files such as images, documents, and uploads',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'Media',
                'url' => route('loom.media.index'),
                'route' => 'loom.media.*',
                'icon' => 'bi-images',
                'order' => 10,
            ],
        ];
    }
}
