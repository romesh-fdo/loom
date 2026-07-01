<?php

namespace Loom\Asdasd;

use Loom\System\PluginBase;
use stdClass;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'asdasd',
            'description' => 'Manage asdasd for Loom CMS',
            'author' => 'Loom',
            'version' => '1.0.0',
        ];
    }

    public function registerNavigation(): array
    {
        return [
            [
                'label' => 'asdasd',
                'url' => route('loom.asdasd.index'),
                'route' => 'loom.asdasd.*',
                'icon' => 'bi-box',
                'order' => 200,
            ],
        ];
    }

    public function registerForms(): array
    {
        return [
            'basic-form' => ['schema' => 'basic'],
        ];
    }

    public function registerFunctions(): array
    {
        return [
            'getBySlug' => [
                'label' => 'Get featured by slug',
                'handler' => 'getBySlug',
                'parameters' => [
                    [
                        'name' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'dynamic' => true,
                    ],
                ],
                'returns' => [
                    ['name' => 'name', 'label' => 'Name'],
                    ['name' => 'seo_description', 'label' => 'SEO description'],
                ],
            ],
        ];
    }

    public function getBySlug(?string $slug): stdClass
    {
        $slug = trim((string) ($slug ?? ''));

        if ($slug === '') {
            $slug = 'home';
        }

        return (object) [
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'seo_description' => 'Custom: '.$slug,
        ];
    }
}
