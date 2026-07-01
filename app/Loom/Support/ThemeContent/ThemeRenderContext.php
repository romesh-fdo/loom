<?php

namespace Loom\Support\ThemeContent;

class ThemeRenderContext
{
    /**
     * @param  array<string, mixed>  $bindings
     */
    public function __construct(
        public readonly string $themeSlug,
        public readonly array $bindings = [],
    ) {}
}
