<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\ThemeAssetsDirective;
use Loom\Support\ThemeContent\ThemeAssetTagBuilder;
use Loom\Support\ThemeContent\ThemeAssetUrlResolver;
use Loom\Support\ThemeContent\ThemeDirectiveArrayParser;
use Loom\Support\ThemeContent\ThemeRenderContext;
use Loom\Support\ThemeContent\ThemeTemplateRenderer;
use Tests\TestCase;

class ThemeAssetDirectiveTest extends TestCase
{
    private ThemeRenderContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new ThemeRenderContext('custom');
    }

    public function test_it_parses_php_style_array_literals(): void
    {
        $parsed = ThemeDirectiveArrayParser::parse(<<<'LITERAL'
[
    ['css/bootstrap.min.css', 'stylesheet'],
    ['img/hero.jpg', 'img', 'alt' => 'Hero'],
]
LITERAL);

        $this->assertIsArray($parsed);
        $this->assertCount(2, $parsed);
        $this->assertSame('css/bootstrap.min.css', $parsed[0][0]);
        $this->assertSame('stylesheet', $parsed[0][1]);
        $this->assertSame('img/hero.jpg', $parsed[1][0]);
        $this->assertSame('img', $parsed[1][1]);
        $this->assertSame('Hero', $parsed[1]['alt']);
    }

    public function test_it_returns_null_for_malformed_array_literals(): void
    {
        $this->assertNull(ThemeDirectiveArrayParser::parse('not-an-array'));
        $this->assertNull(ThemeDirectiveArrayParser::parse('[1, 2,'));
    }

    public function test_it_resolves_theme_asset_urls(): void
    {
        $resolver = new ThemeAssetUrlResolver;
        $url = $resolver->resolve('css/foo.css', $this->context);

        $this->assertStringContainsString('/theme/custom/assets/css/foo.css', $url);
    }

    public function test_it_passes_through_external_urls(): void
    {
        $resolver = new ThemeAssetUrlResolver;
        $external = 'https://fonts.googleapis.com/css2?family=Test&display=swap';
        $url = $resolver->resolve($external, $this->context);

        $this->assertSame($external, $url);
    }

    public function test_it_builds_stylesheet_and_script_tags(): void
    {
        $builder = new ThemeAssetTagBuilder(new ThemeAssetUrlResolver);

        $stylesheet = $builder->build(['css/app.css', 'stylesheet'], $this->context);
        $this->assertStringContainsString('<link', $stylesheet);
        $this->assertStringContainsString('rel="stylesheet"', $stylesheet);
        $this->assertStringContainsString('/theme/custom/assets/css/app.css', $stylesheet);

        $script = $builder->build(['js/app.js', 'script'], $this->context);
        $this->assertStringContainsString('<script', $script);
        $this->assertStringContainsString('/theme/custom/assets/js/app.js', $script);
        $this->assertStringContainsString('</script>', $script);
    }

    public function test_it_builds_tags_from_attribute_arrays(): void
    {
        $builder = new ThemeAssetTagBuilder(new ThemeAssetUrlResolver);

        $tag = $builder->build([
            'https://fonts.googleapis.com/css2?family=Test&display=swap',
            ['rel' => 'stylesheet', 'crossorigin' => 'anonymous'],
        ], $this->context);

        $this->assertStringContainsString('rel="stylesheet"', $tag);
        $this->assertStringContainsString('crossorigin="anonymous"', $tag);
        $this->assertStringContainsString('href="https://fonts.googleapis.com/css2?family=Test&amp;display=swap"', $tag);
    }

    public function test_it_renders_assets_directive_into_html(): void
    {
        $directive = new ThemeAssetsDirective;
        $template = <<<'TEMPLATE'
@assets([
    ['css/bootstrap.min.css', 'stylesheet'],
    ['css/aos.css', 'stylesheet'],
    ['js/main.js', 'script'],
])
TEMPLATE;

        $html = $directive->render($template, $this->context);

        $this->assertStringContainsString('rel="stylesheet"', $html);
        $this->assertStringContainsString('/theme/custom/combine/', $html);
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('/theme/custom/assets/js/main.js', $html);
        $this->assertStringNotContainsString('@assets', $html);
    }

    public function test_malformed_assets_directive_renders_empty_string(): void
    {
        $directive = new ThemeAssetsDirective;
        $html = $directive->render('@assets(not valid)', $this->context);

        $this->assertSame('', $html);
    }

    public function test_template_renderer_processes_assets_last(): void
    {
        $template = <<<'TEMPLATE'
@assets([
    ['css/style.css', 'stylesheet'],
])
TEMPLATE;

        $html = ThemeTemplateRenderer::renderSegment($template, [], [], $this->context);

        $this->assertStringContainsString('/theme/custom/assets/css/style.css', $html);
        $this->assertStringNotContainsString('@assets', $html);
    }

    public function test_template_renderer_skips_assets_without_context(): void
    {
        $template = "@assets([\n    ['css/style.css', 'stylesheet'],\n])";

        $html = ThemeTemplateRenderer::renderSegment($template, []);

        $this->assertStringContainsString('@assets', $html);
    }
}
