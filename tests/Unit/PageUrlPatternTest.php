<?php

namespace Tests\Unit;

use Loom\Support\ThemeContent\PageUrlPattern;
use Tests\TestCase;

class PageUrlPatternTest extends TestCase
{
    public function test_it_extracts_placeholders_from_page_urls(): void
    {
        $this->assertSame(['id'], PageUrlPattern::extractPlaceholders('products/{id}'));
        $this->assertSame(['category', 'slug'], PageUrlPattern::extractPlaceholders('blog/{category}/{slug}'));
        $this->assertSame([], PageUrlPattern::extractPlaceholders('about-us'));
    }

    public function test_it_detects_pattern_urls(): void
    {
        $this->assertTrue(PageUrlPattern::isPattern('products/{id}'));
        $this->assertFalse(PageUrlPattern::isPattern('products/widget'));
    }

    public function test_it_builds_template_keys_for_duplicate_detection(): void
    {
        $this->assertSame(
            PageUrlPattern::templateKey('products/{id}'),
            PageUrlPattern::templateKey('products/{slug}')
        );
        $this->assertNotSame(
            PageUrlPattern::templateKey('products/{id}'),
            PageUrlPattern::templateKey('shop/{id}')
        );
    }

    public function test_it_matches_incoming_paths_against_patterns(): void
    {
        $this->assertSame(['id' => '42'], PageUrlPattern::match('products/{id}', 'products/42'));
        $this->assertNull(PageUrlPattern::match('products/{id}', 'shop/42'));
        $this->assertSame([], PageUrlPattern::match('about', 'about'));
    }

    public function test_it_counts_static_segments_for_match_priority(): void
    {
        $this->assertSame(1, PageUrlPattern::staticSegmentCount('products/{id}'));
        $this->assertSame(2, PageUrlPattern::staticSegmentCount('products/special/{id}'));
    }
}
