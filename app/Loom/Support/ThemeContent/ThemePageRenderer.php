<?php

namespace Loom\Support\ThemeContent;

use Loom\Support\ThemeManager;

class ThemePageRenderer
{
    /**
     * @var list<string>
     */
    private const LAYOUT_SLOTS = [
        'topbar',
        'header',
        'footer',
        'search_overlay',
        'scroll_to_top',
        'body_end',
    ];

    public function __construct(
        protected BlockStore $blocks,
        protected SegmentStore $segments,
        protected ThemeManager $themes,
    ) {}

    public function render(ThemeFileRecord $page, ?string $themeSlug = null): string
    {
        $themeSlug ??= $this->themes->activeSlug();
        $sections = is_array($page->sections ?? null) ? $page->sections : [];
        $html = '';

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $blockSlug = $section['block_slug'] ?? null;

            if (! is_string($blockSlug) || $blockSlug === '') {
                continue;
            }

            $block = $this->blocks->find($blockSlug, $themeSlug);

            if ($block === null) {
                continue;
            }

            $code = is_array($block->code ?? null) ? $block->code : [];
            $template = (string) ($code['template'] ?? '');
            $parameters = is_array($code['parameters'] ?? null) ? $code['parameters'] : [];
            $values = is_array($section['values'] ?? null) ? $section['values'] : [];

            $html .= ThemeTemplateRenderer::renderBlock(
                $template,
                $values,
                $parameters,
                new ThemeRenderContext($themeSlug),
            );
        }

        return $html;
    }

    /**
     * @return array<string, string>
     */
    public function renderLayoutSlots(?string $themeSlug = null): array
    {
        $themeSlug ??= $this->themes->activeSlug();
        $slots = [];

        foreach (self::LAYOUT_SLOTS as $slot) {
            $slots[$slot] = $this->renderSlot($slot, $themeSlug);
        }

        return $slots;
    }

    public function renderSlot(string $slot, ?string $themeSlug = null): string
    {
        $themeSlug ??= $this->themes->activeSlug();
        $segment = $this->segments->findBySlot($slot, $themeSlug);

        if ($segment === null || ! ($segment->enabled ?? true)) {
            return '';
        }

        $code = is_array($segment->code ?? null) ? $segment->code : [];
        $template = (string) ($code['template'] ?? '');
        $parameters = is_array($code['parameters'] ?? null) ? $code['parameters'] : [];

        return ThemeTemplateRenderer::renderSegment(
            $template,
            [],
            $parameters,
            new ThemeRenderContext($themeSlug),
        );
    }
}
