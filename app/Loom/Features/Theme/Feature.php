<?php

namespace Loom\Features\Theme;

use Loom\Features\FeatureBase;

class Feature extends FeatureBase
{
    public function featureDetails(): array
    {
        return [
            'name' => 'Theme',
            'description' => 'Manage site themes and active theme selection',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }
}
