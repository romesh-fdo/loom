<?php

namespace Loom\Builder;

class PluginPhpPatcher
{
    public function __construct(
        protected SecureFileWriter $writer,
    ) {}

    public function patch(Blueprint $blueprint): ?string
    {
        $content = $this->writer->read($blueprint->pluginSlug(), 'Plugin.php');

        if ($content === null) {
            return null;
        }

        $label = $this->escapePhpString($blueprint->pluginLabel());
        $icon = $this->escapePhpString($blueprint->pluginIcon());
        $route = $blueprint->routeSlug();

        $content = preg_replace(
            "/(public function pluginDetails\(\): array\s*\{[\s\S]*?'name' => ')[^']*'/",
            '$1'.$label.'\'',
            $content,
            1
        ) ?? $content;

        $content = preg_replace(
            "/(public function registerNavigation\(\): array\s*\{[\s\S]*?'label' => ')[^']*'/",
            '$1'.$label.'\'',
            $content,
            1
        ) ?? $content;

        $content = preg_replace(
            "/(public function registerNavigation\(\): array\s*\{[\s\S]*?'icon' => ')[^']*'/",
            '$1'.$icon.'\'',
            $content,
            1
        ) ?? $content;

        $content = preg_replace(
            "/route\('loom\.[^']+\.index'\)/",
            "route('loom.{$route}.index')",
            $content
        ) ?? $content;

        $content = preg_replace(
            "/'route' => 'loom\.[^']+\.\*'/",
            "'route' => 'loom.{$route}.*'",
            $content
        ) ?? $content;

        return $content;
    }

    protected function escapePhpString(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
