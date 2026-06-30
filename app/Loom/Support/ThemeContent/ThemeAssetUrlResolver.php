<?php

namespace Loom\Support\ThemeContent;

class ThemeAssetUrlResolver
{
    public function resolve(string $path, ThemeRenderContext $context): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return route('theme.assets', [
            'theme' => $context->themeSlug,
            'path' => $path,
        ]);
    }
}
