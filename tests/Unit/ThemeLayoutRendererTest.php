<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\SegmentStore;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemeLayoutRenderer;
use Loom\Support\ThemeContent\ThemeRenderContext;
use Loom\Support\ThemeContent\ThemeTemplateRenderer;
use Loom\Support\ThemeManager;
use Mockery;
use Tests\TestCase;

class ThemeLayoutRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_renders_assets_directives_inside_segments(): void
    {
        $segment = new ThemeFileRecord('css', [
            'name' => 'css',
            'slug' => 'css',
            'enabled' => true,
            'code' => [
                'template' => "@assets([\n    ['css/bootstrap.min.css', 'stylesheet'],\n])",
                'parameters' => [],
            ],
        ]);

        $segments = Mockery::mock(SegmentStore::class);
        $segments->shouldReceive('find')
            ->once()
            ->with('css', 'custom')
            ->andReturn($segment);

        $themes = Mockery::mock(ThemeManager::class);
        $themes->shouldReceive('activeSlug')->andReturn('custom');

        $renderer = new ThemeLayoutRenderer($segments, $themes);

        $layout = new ThemeFileRecord('custom', [
            'name' => 'Custom',
            'slug' => 'custom',
            'code' => "@segment('css', [])",
        ]);

        $html = $renderer->render($layout, '', 'custom');

        $this->assertStringContainsString('rel="stylesheet"', $html);
        $this->assertStringContainsString('/theme/custom/assets/css/bootstrap.min.css', $html);
        $this->assertStringNotContainsString('@assets', $html);
    }

    public function test_render_segment_receives_context_from_layout_renderer(): void
    {
        $context = new ThemeRenderContext('custom');
        $template = "@assets([\n    ['css/style.css', 'stylesheet'],\n])";

        $html = ThemeTemplateRenderer::renderSegment($template, [], [], $context);

        $this->assertStringContainsString('/theme/custom/assets/css/style.css', $html);
        $this->assertStringNotContainsString('@assets', $html);
    }
}
