<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\PageLayoutFieldsComposer;
use Tests\TestCase;

class PageLayoutFieldsComposerTest extends TestCase
{
    public function test_it_composes_static_layout_fields(): void
    {
        $php = PageLayoutFieldsComposer::toPhpBlock([
            'meta' => [
                'author' => 'Sarab',
                'description' => 'About page',
            ],
        ]);

        $this->assertStringContainsString('@php', $php);
        $this->assertStringContainsString('$layoutFields =', $php);
        $this->assertStringContainsString("'author' => 'Sarab'", $php);
        $this->assertStringContainsString('@endphp', $php);

        $parsed = PageLayoutFieldsComposer::fromPhpBlock($php);

        $this->assertSame('Sarab', $parsed['meta']['author']);
        $this->assertSame('About page', $parsed['meta']['description']);
    }

    public function test_it_composes_and_parses_import_dynamic_layout_fields(): void
    {
        $php = PageLayoutFieldsComposer::toPhpBlock([
            'meta' => [
                'author' => 'Sarab',
                'description' => [
                    'import' => 'productDetails',
                    'field' => 'seo_description',
                ],
            ],
        ]);

        $this->assertStringContainsString('$productDetails->seo_description', $php);
        $this->assertStringContainsString("'description' =>", $php);

        $parsed = PageLayoutFieldsComposer::fromPhpBlock($php);

        $this->assertSame('Sarab', $parsed['meta']['author']);
        $this->assertSame('productDetails', $parsed['meta']['description']['import']);
        $this->assertSame('seo_description', $parsed['meta']['description']['field']);
        $this->assertSame('productDetails.seo_description', $parsed['meta']['description']['dynamic']);
    }

    public function test_it_validates_variable_names(): void
    {
        $this->assertTrue(PageLayoutFieldsComposer::isValidVariableName('productSeo'));
        $this->assertFalse(PageLayoutFieldsComposer::isValidVariableName('9invalid'));
    }
}
