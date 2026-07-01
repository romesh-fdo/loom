<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemePageRenderer;
use Mockery;
use Tests\TestCase;

class ThemePageRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_renders_blocks_from_page_sections(): void
    {
        $block = new ThemeFileRecord('hero', [
            'name' => 'Hero',
            'slug' => 'hero',
            'code' => [
                'template' => '<h1>{{ $blockData[\'hero_header\'] }}</h1>',
                'parameters' => [
                    ['name' => 'hero_header', 'type' => 'text'],
                ],
            ],
        ]);

        $blocks = Mockery::mock('Loom\Support\ThemeContent\BlockStore');
        $blocks->shouldReceive('find')->once()->with('hero', 'custom')->andReturn($block);

        $segments = Mockery::mock('Loom\Support\ThemeContent\SegmentStore');
        $themes = Mockery::mock('Loom\Support\ThemeManager');
        $themes->shouldReceive('activeSlug')->andReturn('custom');

        $page = new ThemeFileRecord('home', [
            'name' => 'Home',
            'slug' => 'home',
            'sections' => [
                [
                    'block_slug' => 'hero',
                    'values' => ['hero_header' => 'Hello World'],
                ],
            ],
        ]);

        $renderer = new ThemePageRenderer($blocks, $segments, $themes);
        $html = $renderer->render($page, 'custom');

        $this->assertStringContainsString('<h1>Hello World</h1>', $html);
    }
}
