<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\ThemeDirectiveParser;
use Tests\TestCase;

class ThemeDirectiveParserTest extends TestCase
{
    public function test_it_parses_block_directives_with_nested_arrays(): void
    {
        $template = "@block('hero', [
    'hero_header' => 'Hello',
    'youtube_link' => [
        'url' => 'https://example.com',
        'class' => 'btn',
        'id' => '',
        'target' => '',
    ],
])";

        $directives = ThemeDirectiveParser::parseBlockDirectives($template);

        $this->assertCount(1, $directives);
        $this->assertSame('hero', $directives[0]['blockSlug']);
        $this->assertSame('Hello', $directives[0]['values']['hero_header']);
        $this->assertSame('https://example.com', $directives[0]['values']['youtube_link']['url']);
    }

    public function test_it_formats_block_directives(): void
    {
        $formatted = ThemeDirectiveParser::formatBlockDirective('hero', [
            'hero_header' => 'Hello',
            'enabled' => true,
            'count' => 3,
        ]);

        $this->assertStringContainsString("@block('hero',", $formatted);
        $this->assertStringContainsString("'hero_header' => 'Hello'", $formatted);
        $this->assertStringContainsString("'enabled' => true", $formatted);
        $this->assertStringContainsString("'count' => 3", $formatted);
    }

    public function test_it_parses_segment_directives(): void
    {
        $template = "@segment('meta', ['author' => 'Sarab', 'description' => 'Test'])";

        $directives = ThemeDirectiveParser::parseSegmentDirectives($template);

        $this->assertCount(1, $directives);
        $this->assertSame('meta', $directives[0]['path']);
        $this->assertSame('Sarab', $directives[0]['params']['author']);
        $this->assertSame('Test', $directives[0]['params']['description']);
    }

    public function test_roundtrip_block_directive(): void
    {
        $values = [
            'title' => 'Line with \'quote\'',
            'nested' => ['url' => 'https://loom.test', 'alt' => ''],
        ];

        $template = ThemeDirectiveParser::formatBlockDirective('cta', $values);
        $parsed = ThemeDirectiveParser::parseBlockDirectives($template);

        $this->assertCount(1, $parsed);
        $this->assertSame('cta', $parsed[0]['blockSlug']);
        $this->assertSame($values['title'], $parsed[0]['values']['title']);
        $this->assertSame($values['nested']['url'], $parsed[0]['values']['nested']['url']);
    }
}
