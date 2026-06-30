<?php

namespace Loom\Support\ThemeContent;

class ThemeAssetCombiner
{
    /**
     * @param  list<string>  $paths
     */
    public function bundleUrl(string $themeSlug, string $assetType, array $paths): string
    {
        $signature = $this->registerManifest($themeSlug, $assetType, $paths);
        $extension = $assetType === 'script' ? 'js' : 'css';

        return route('theme.assets.combine', [
            'theme' => $themeSlug,
            'signature' => $signature,
            'extension' => $extension,
        ]);
    }

    /**
     * @return array{theme: string, type: string, paths: list<string>}|null
     */
    public function manifest(string $signature): ?array
    {
        $path = $this->manifestPath($signature);

        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return null;
        }

        $theme = $data['theme'] ?? null;
        $type = $data['type'] ?? null;
        $paths = $data['paths'] ?? null;

        if (! is_string($theme) || ! is_string($type) || ! is_array($paths)) {
            return null;
        }

        $normalizedPaths = [];

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                $normalizedPaths[] = $path;
            }
        }

        if ($normalizedPaths === []) {
            return null;
        }

        return [
            'theme' => $theme,
            'type' => $type,
            'paths' => $normalizedPaths,
        ];
    }

    /**
     * @param  list<string>  $paths
     */
    public function render(string $themeSlug, string $assetType, array $paths): string
    {
        $signature = $this->signature($themeSlug, $assetType, $paths);
        $cachePath = $this->cachePath($signature, $assetType);

        if ($this->isCacheFresh($cachePath, $themeSlug, $paths)) {
            return (string) file_get_contents($cachePath);
        }

        $combined = $this->combine($themeSlug, $assetType, $paths);

        if (! is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0755, true);
        }

        file_put_contents($cachePath, $combined);

        return $combined;
    }

    public static function isBundleablePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return true;
    }

    public static function isBundlableShorthand(string $shorthand): bool
    {
        return in_array($shorthand, ['stylesheet', 'script'], true);
    }

    /**
     * @param  list<string>  $paths
     */
    private function combine(string $themeSlug, string $assetType, array $paths): string
    {
        $assetsRoot = base_path('theme/'.$themeSlug.'/assets');
        $chunks = [];

        foreach ($paths as $path) {
            $filePath = $assetsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

            if (! is_file($filePath)) {
                continue;
            }

            $content = (string) file_get_contents($filePath);

            if ($assetType === 'stylesheet') {
                $content = $this->rewriteCssUrls($content, $path, $themeSlug);
            }

            if ($this->shouldMinify($path, $assetType)) {
                $content = $this->minify($content, $assetType);
            }

            $chunks[] = trim($content);
        }

        $separator = $assetType === 'script' ? ";\n" : "\n";

        return implode($separator, array_filter($chunks, static fn (string $chunk): bool => $chunk !== '')).$separator;
    }

    private function rewriteCssUrls(string $css, string $sourcePath, string $themeSlug): string
    {
        $baseDir = str_replace('\\', '/', dirname($sourcePath));
        $baseDir = $baseDir === '.' ? '' : trim($baseDir, '/');

        return preg_replace_callback(
            '/url\(\s*([\'"]?)(?!https?:|data:|#)([^\'")]+)\1\s*\)/i',
            function (array $matches) use ($baseDir, $themeSlug): string {
                $url = trim($matches[2]);

                if ($url === '' || str_starts_with($url, '/')) {
                    return $matches[0];
                }

                $resolved = $baseDir !== '' ? $baseDir.'/'.$url : $url;
                $resolved = preg_replace('#(^|/)\./#', '$1', str_replace('//', '/', $resolved)) ?? $resolved;
                $resolved = preg_replace('#[^/]+/\.\./#', '', $resolved) ?? $resolved;

                return 'url(/theme/'.$themeSlug.'/assets/'.$resolved.')';
            },
            $css
        ) ?? $css;
    }

    private function shouldMinify(string $path, string $assetType): bool
    {
        if (! (bool) config('loom.assets.minify', true)) {
            return false;
        }

        $lower = strtolower($path);

        if (str_ends_with($lower, '.min.css') || str_ends_with($lower, '.min.js')) {
            return false;
        }

        return $assetType === 'stylesheet' || $assetType === 'script';
    }

    private function minify(string $content, string $assetType): string
    {
        if ($assetType === 'stylesheet') {
            $content = preg_replace('!/\*.*?\*/!s', '', $content) ?? $content;
            $content = preg_replace('/\s+/', ' ', $content) ?? $content;

            return trim(str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;'], ['{', '{', '}', '}', ':', ':', ';', ';'], $content));
        }

        $content = preg_replace('#/\*.*?\*/#s', '', $content) ?? $content;
        $content = preg_replace('#//(?![^\n]*[\'"]).*$#m', '', $content) ?? $content;
        $content = preg_replace('/\s+/', ' ', $content) ?? $content;

        return trim($content);
    }

    /**
     * @param  list<string>  $paths
     */
    private function registerManifest(string $themeSlug, string $assetType, array $paths): string
    {
        $signature = $this->signature($themeSlug, $assetType, $paths);
        $manifestPath = $this->manifestPath($signature);

        if (! is_dir(dirname($manifestPath))) {
            mkdir(dirname($manifestPath), 0755, true);
        }

        file_put_contents($manifestPath, json_encode([
            'theme' => $themeSlug,
            'type' => $assetType,
            'paths' => array_values($paths),
        ], JSON_UNESCAPED_SLASHES));

        return $signature;
    }

    /**
     * @param  list<string>  $paths
     */
    private function signature(string $themeSlug, string $assetType, array $paths): string
    {
        $assetsRoot = base_path('theme/'.$themeSlug.'/assets');
        $fingerprint = [];

        foreach ($paths as $path) {
            $filePath = $assetsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
            $fingerprint[] = $path.':'.(is_file($filePath) ? filemtime($filePath) : '0');
        }

        return hash('xxh128', $themeSlug.'|'.$assetType.'|'.implode('|', $fingerprint));
    }

    /**
     * @param  list<string>  $paths
     */
    private function isCacheFresh(string $cachePath, string $themeSlug, array $paths): bool
    {
        if (! is_file($cachePath)) {
            return false;
        }

        $cacheMtime = filemtime($cachePath);
        $assetsRoot = base_path('theme/'.$themeSlug.'/assets');

        foreach ($paths as $path) {
            $filePath = $assetsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

            if (! is_file($filePath) || filemtime($filePath) > $cacheMtime) {
                return false;
            }
        }

        return true;
    }

    private function manifestPath(string $signature): string
    {
        return storage_path('framework/loom/asset-bundles/'.$signature.'.json');
    }

    private function cachePath(string $signature, string $assetType): string
    {
        $extension = $assetType === 'script' ? 'js' : 'css';

        return storage_path('framework/loom/asset-bundles/'.$signature.'.'.$extension);
    }
}
