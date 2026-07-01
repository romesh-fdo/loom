<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\LayoutFieldResolver;
use Loom\Support\ThemeContent\ThemeRenderContext;
use Tests\TestCase;

class LayoutFieldResolverTest extends TestCase
{
    public function test_it_resolves_static_and_dynamic_layout_fields(): void
    {
        $context = new ThemeRenderContext('custom', [
            'product' => (object) ['seo_description' => 'Dynamic SEO'],
        ]);

        $resolved = LayoutFieldResolver::resolveForSegment([
            'meta' => [
                'author' => 'Static Author',
                'description' => ['dynamic' => 'product.seo_description'],
            ],
        ], 'meta', $context);

        $this->assertSame('Static Author', $resolved['author']);
        $this->assertSame('Dynamic SEO', $resolved['description']);
    }
}
