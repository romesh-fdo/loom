<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeManager;
use Mockery;
use Tests\TestCase;

class PageStoreTest extends TestCase
{
    protected string $themePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themePath = storage_path('framework/testing/pages-'.uniqid());
        File::makeDirectory($this->themePath.'/custom/pages', 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->themePath)) {
            File::deleteDirectory($this->themePath);
        }

        Mockery::close();

        parent::tearDown();
    }

    protected function makeStore(): PageStore
    {
        $themes = Mockery::mock(ThemeManager::class);
        $themes->shouldReceive('activeSlug')->andReturn('custom');
        $themes->shouldReceive('themesPath')->andReturn($this->themePath);

        return new PageStore($themes);
    }

    public function test_it_writes_and_reads_page_blade_files(): void
    {
        $store = $this->makeStore();

        $store->create([
            'name' => 'About',
            'url' => 'about',
            'layout' => 'custom',
            'layout_fields' => [
                'meta' => [
                    'author' => 'Sarab',
                    'description' => 'About page',
                ],
            ],
            'sections' => [
                [
                    'block_slug' => 'hero',
                    'values' => [
                        'hero_header' => 'Welcome',
                    ],
                ],
            ],
        ], 'custom');

        $record = $store->find('about', 'custom');

        $this->assertNotNull($record);
        $this->assertSame('about', $record->slug);
        $this->assertSame('custom', $record->layout);
        $this->assertSame('Sarab', $record->layout_fields['meta']['author']);
        $this->assertCount(1, $record->sections);
        $this->assertSame('hero', $record->sections[0]['block_slug']);
        $this->assertFileExists($this->themePath.'/custom/pages/about.blade.php');
        $fileContents = file_get_contents($this->themePath.'/custom/pages/about.blade.php');
        $this->assertStringContainsString("@block('hero',", $fileContents);
        $this->assertStringContainsString('$layoutFields =', $fileContents);
        $this->assertStringNotContainsString('"layout_fields"', $fileContents);
    }

    public function test_it_migrates_legacy_page_json_directories(): void
    {
        $store = $this->makeStore();

        $legacyDir = $this->themePath.'/custom/pages/legacy-page';
        File::makeDirectory($legacyDir, 0755, true);
        File::put($legacyDir.'/'.PageStore::PAGE_JSON_FILENAME, json_encode([
            'name' => 'Legacy',
            'slug' => 'legacy-page',
            'url' => 'legacy-page',
            'layout' => 'custom',
            'sections' => [
                ['block_slug' => 'hero', 'values' => ['hero_header' => 'Legacy']],
            ],
            'updated_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        $records = $store->all('custom');

        $this->assertCount(1, $records);
        $this->assertSame('legacy-page', $records->first()->slug);
        $this->assertFileExists($this->themePath.'/custom/pages/legacy-page.blade.php');
        $this->assertFileDoesNotExist($legacyDir.'/'.PageStore::PAGE_JSON_FILENAME);
    }

    public function test_it_matches_dynamic_page_urls_and_prefers_static_urls(): void
    {
        $store = $this->makeStore();

        $store->create([
            'name' => 'Product detail',
            'url' => 'products/{id}',
            'layout' => 'custom',
            'sections' => [],
        ], 'custom');

        $store->create([
            'name' => 'Product special',
            'url' => 'products/special',
            'layout' => 'custom',
            'sections' => [],
        ], 'custom');

        $dynamicMatch = $store->matchPath('products/42', 'custom');
        $staticMatch = $store->matchPath('products/special', 'custom');

        $this->assertNotNull($dynamicMatch);
        $this->assertSame('product-detail', $dynamicMatch->page->slug);
        $this->assertSame(['id' => '42'], $dynamicMatch->params);

        $this->assertNotNull($staticMatch);
        $this->assertSame('product-special', $staticMatch->page->slug);
        $this->assertSame([], $staticMatch->params);
    }

    public function test_it_generates_file_slug_from_page_name_not_url(): void
    {
        $store = $this->makeStore();

        $store->create([
            'name' => 'Test Page',
            'url' => 'product/{id}',
            'layout' => 'custom',
            'sections' => [],
        ], 'custom');

        $this->assertFileExists($this->themePath.'/custom/pages/test-page.blade.php');
        $this->assertFileDoesNotExist($this->themePath.'/custom/pages/productid.blade.php');

        $record = $store->find('test-page', 'custom');

        $this->assertNotNull($record);
        $this->assertSame('test-page', $record->slug);
        $this->assertSame('Test Page', $record->name);
        $this->assertSame('product/{id}', $record->url);
    }

    public function test_it_deletes_page_blade_files(): void
    {
        $store = $this->makeStore();

        $store->create([
            'name' => 'Test Page',
            'url' => 'product/{id}',
            'layout' => 'custom',
            'sections' => [],
        ], 'custom');

        $this->assertFileExists($this->themePath.'/custom/pages/test-page.blade.php');

        $store->delete('test-page', 'custom');

        $this->assertFileDoesNotExist($this->themePath.'/custom/pages/test-page.blade.php');
        $this->assertNull($store->find('test-page', 'custom'));
    }

    public function test_it_rejects_duplicate_pattern_templates_via_url_exists(): void
    {
        $store = $this->makeStore();

        $store->create([
            'name' => 'Product detail',
            'url' => 'products/{id}',
            'layout' => 'custom',
            'sections' => [],
        ], 'custom');

        $this->assertTrue($store->urlExists('products/{slug}', null, 'custom'));
        $this->assertFalse($store->urlExists('shop/{id}', null, 'custom'));
    }
}
