<?php

namespace Loom\Http\Controllers\Concerns;

use Loom\Support\ThemeManager;

trait ResolvesActiveTheme
{
    protected function activeThemeSlug(): string
    {
        return app(ThemeManager::class)->activeSlug();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function activeTheme(): ?array
    {
        return app(ThemeManager::class)->find($this->activeThemeSlug());
    }
}
