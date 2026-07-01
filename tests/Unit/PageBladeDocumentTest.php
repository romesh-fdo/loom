<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\PageBladeDocument;
use Tests\TestCase;

class PageBladeDocumentTest extends TestCase
{
    public function test_it_parses_meta_php_layout_fields_and_verbatim_blocks(): void
    {
        $contents = <<<'BLADE'
{{-- loom:meta
{
    "name": "Home",
    "slug": "home",
    "url": "",
    "layout": "custom",
    "updated_at": "2026-06-30T17:10:04+00:00"
}
--}}

@php
    $layoutFields = [
        'meta' => [
            'author' => 'Sarab',
        ],
    ];
@endphp

@verbatim
@block('hero', [
    'hero_header' => 'Hello',
])
@endverbatim
BLADE;

        $parsed = PageBladeDocument::parse($contents);

        $this->assertSame('Home', $parsed['meta']['name']);
        $this->assertSame('Sarab', $parsed['layout_fields']['meta']['author']);
        $this->assertSame([], $parsed['entity_imports']);
        $this->assertStringContainsString("@block('hero',", $parsed['template']);
        $this->assertArrayNotHasKey('layout_fields', $parsed['meta']);
    }

    public function test_it_composes_page_blade_without_layout_fields_in_meta(): void
    {
        $composed = PageBladeDocument::compose(
            [
                'name' => 'Home',
                'slug' => 'home',
                'url' => '',
                'layout' => 'custom',
                'updated_at' => '2026-06-30T17:10:04+00:00',
            ],
            [],
            [
                'meta' => [
                    'author' => 'Sarab',
                ],
            ],
            "@block('hero', [\n    'hero_header' => 'Hello',\n])"
        );

        $this->assertStringContainsString('loom:meta', $composed);
        $this->assertStringNotContainsString('"layout_fields"', $composed);
        $this->assertStringContainsString('$layoutFields =', $composed);
        $this->assertStringContainsString('@verbatim', $composed);
    }

    public function test_it_round_trips_entity_imports_and_dynamic_layout_fields(): void
    {
        $entityImports = [
            [
                'variable' => 'productDetails',
                'plugin' => 'loom.asdasd',
                'function' => 'getById',
                'parameters' => [
                    'id' => ['mode' => 'path_param', 'param' => 'id'],
                ],
            ],
        ];

        $layoutFields = [
            'meta' => [
                'author' => [
                    'import' => 'productDetails',
                    'field' => 'id',
                ],
            ],
        ];

        $composed = PageBladeDocument::compose(
            ['name' => 'Product', 'slug' => 'products-id', 'url' => 'products/{id}', 'layout' => 'custom'],
            $entityImports,
            $layoutFields,
            ''
        );

        $parsed = PageBladeDocument::parse($composed);

        $this->assertCount(1, $parsed['entity_imports']);
        $this->assertSame('productDetails', $parsed['entity_imports'][0]['variable']);
        $this->assertSame('loom.asdasd', $parsed['entity_imports'][0]['plugin']);
        $this->assertSame('getById', $parsed['entity_imports'][0]['function']);
        $this->assertSame('path_param', $parsed['entity_imports'][0]['parameters']['id']['mode']);
        $this->assertSame('id', $parsed['entity_imports'][0]['parameters']['id']['param']);
        $this->assertSame('productDetails', $parsed['layout_fields']['meta']['author']['import']);
        $this->assertSame('id', $parsed['layout_fields']['meta']['author']['field']);
    }
}
