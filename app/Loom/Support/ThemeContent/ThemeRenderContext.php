<?php

namespace Loom\Support\ThemeContent;

class ThemeRenderContext
{
    public function __construct(
        public readonly string $themeSlug,
    ) {}
}
