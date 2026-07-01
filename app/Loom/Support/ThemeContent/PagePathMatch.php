<?php

namespace Loom\Support\ThemeContent;

class PagePathMatch
{
    /**
     * @param  array<string, string>  $params
     */
    public function __construct(
        public ThemeFileRecord $page,
        public array $params = [],
    ) {}
}
