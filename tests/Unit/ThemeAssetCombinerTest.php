<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\ThemeAssetCombiner;
use Loom\Support\ThemeContent\ThemeAssetEntryGrouper;
use Loom\Support\ThemeContent\ThemeAssetsDirective;
use Loom\Support\ThemeContent\ThemeRenderContext;
use Tests\TestCase;

class ThemeAssetCombinerTest extends TestCase
{
    public function test_it_groups_consecutive_local_stylesheets_for_bundling(): void
    {
        $groups = (new ThemeAssetEntryGrouper)->group([
            ['css/a.css', 'stylesheet'],
            ['css/b.css', 'stylesheet'],
            ['https://fonts.googleapis.com/css2?family=Test&display=swap', 'stylesheet'],
            ['css/c.css', 'stylesheet'],
            ['js/a.js', 'script'],
            ['js/b.js', 'script'],
        ]);

        $this->assertCount(4, $groups);
        $this->assertSame('bundle', $groups[0]['type']);
        $this->assertSame('stylesheet', $groups[0]['asset_type']);
        $this->assertCount(2, $groups[0]['entries']);
        $this->assertSame('single', $groups[1]['type']);
        $this->assertSame('single', $groups[2]['type']);
        $this->assertSame('bundle', $groups[3]['type']);
        $this->assertSame('script', $groups[3]['asset_type']);
    }

    public function test_it_combines_theme_stylesheets_into_one_response(): void
    {
        $themeDir = base_path('theme/custom/assets/css');
        $fileA = $themeDir.'/bundle-a.css';
        $fileB = $themeDir.'/bundle-b.css';

        file_put_contents($fileA, '.bundle-a { color: red; }');
        file_put_contents($fileB, '.bundle-b { color: blue; }');

        try {
            $combiner = new ThemeAssetCombiner;
            $content = $combiner->render('custom', 'stylesheet', ['css/bundle-a.css', 'css/bundle-b.css']);

            $this->assertStringContainsString('.bundle-a{color:red;}', $content);
            $this->assertStringContainsString('.bundle-b{color:blue;}', $content);
        } finally {
            @unlink($fileA);
            @unlink($fileB);
        }
    }

    public function test_assets_directive_outputs_single_bundle_link_for_multiple_css_files(): void
    {
        $directive = new ThemeAssetsDirective;
        $html = $directive->render(<<<'TEMPLATE'
@assets([
    ['css/bootstrap.min.css', 'stylesheet'],
    ['css/aos.css', 'stylesheet'],
    ['css/style.css', 'stylesheet'],
])
TEMPLATE, new ThemeRenderContext('custom'));

        $this->assertSame(1, substr_count($html, '<link'));
        $this->assertStringContainsString('/theme/custom/combine/', $html);
        $this->assertStringNotContainsString('/theme/custom/assets/css/bootstrap.min.css', $html);
    }

    public function test_assets_directive_keeps_external_stylesheets_separate(): void
    {
        $directive = new ThemeAssetsDirective;
        $html = $directive->render(<<<'TEMPLATE'
@assets([
    ['https://fonts.googleapis.com/css2?family=Test&display=swap', 'stylesheet'],
    ['css/bootstrap.min.css', 'stylesheet'],
    ['css/aos.css', 'stylesheet'],
])
TEMPLATE, new ThemeRenderContext('custom'));

        $this->assertSame(2, substr_count($html, '<link'));
        $this->assertStringContainsString('fonts.googleapis.com', $html);
        $this->assertStringContainsString('/theme/custom/combine/', $html);
    }

    public function test_combine_endpoint_serves_bundled_assets(): void
    {
        $themeDir = base_path('theme/custom/assets/css');
        $fileA = $themeDir.'/endpoint-a.css';
        $fileB = $themeDir.'/endpoint-b.css';

        file_put_contents($fileA, '.endpoint-a { color: green; }');
        file_put_contents($fileB, '.endpoint-b { color: yellow; }');

        try {
            $combiner = new ThemeAssetCombiner;
            $url = $combiner->bundleUrl('custom', 'stylesheet', ['css/endpoint-a.css', 'css/endpoint-b.css']);

            $response = $this->get($url);

            $response->assertOk();
            $response->assertHeader('content-type', 'text/css; charset=UTF-8');
            $this->assertStringContainsString('.endpoint-a{color:green;}', $response->getContent());
            $this->assertStringContainsString('.endpoint-b{color:yellow;}', $response->getContent());
        } finally {
            @unlink($fileA);
            @unlink($fileB);
        }
    }
}
