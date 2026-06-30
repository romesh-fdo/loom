<?php

namespace Loom\Support\ThemeContent;

class ThemeAssetTagBuilder
{
    public function __construct(
        protected ThemeAssetUrlResolver $urlResolver,
    ) {}

    /**
     * @param  array<int|string, mixed>  $entry
     */
    public function build(array $entry, ThemeRenderContext $context): string
    {
        $path = $entry[0] ?? null;

        if (! is_string($path) || $path === '') {
            return '';
        }

        $url = $this->urlResolver->resolve($path, $context);

        if ($url === '') {
            return '';
        }

        $second = $entry[1] ?? null;
        $extraAttrs = $this->extractExtraAttributes($entry);

        if (is_array($second)) {
            return $this->buildFromAttributes($second, $url, $extraAttrs);
        }

        if (! is_string($second) || $second === '') {
            return '';
        }

        return $this->buildFromShorthand($second, $url, $extraAttrs);
    }

    /**
     * @param  array<int|string, mixed>  $entry
     * @return array<string, string>
     */
    private function extractExtraAttributes(array $entry): array
    {
        $attrs = [];

        foreach ($entry as $key => $value) {
            if (! is_string($key) || is_numeric($key)) {
                continue;
            }

            if (! is_string($value) && ! is_numeric($value) && ! is_bool($value)) {
                continue;
            }

            $attrs[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $attrs;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $extraAttrs
     */
    private function buildFromAttributes(array $attributes, string $url, array $extraAttrs): string
    {
        $attrs = array_merge($attributes, $extraAttrs);
        $rel = is_string($attrs['rel'] ?? null) ? $attrs['rel'] : '';

        if (in_array($rel, ['stylesheet', 'preload', 'preconnect', 'dns-prefetch'], true)) {
            $attrs['href'] = $url;

            return $this->renderTag('link', $attrs, true);
        }

        if ($rel === '' && ! isset($attrs['href']) && ! isset($attrs['src'])) {
            $attrs['href'] = $url;

            return $this->renderTag('link', $attrs, true);
        }

        if (! isset($attrs['href']) && ! isset($attrs['src'])) {
            $attrs['src'] = $url;
        }

        $tag = isset($attrs['src']) ? 'script' : 'img';

        return $this->renderTag($tag, $attrs, $tag === 'link');
    }

    /**
     * @param  array<string, string>  $extraAttrs
     */
    private function buildFromShorthand(string $shorthand, string $url, array $extraAttrs): string
    {
        return match ($shorthand) {
            'stylesheet' => $this->renderTag('link', array_merge([
                'rel' => 'stylesheet',
                'href' => $url,
            ], $extraAttrs), true),
            'preload' => $this->renderTag('link', array_merge([
                'rel' => 'preload',
                'href' => $url,
            ], $extraAttrs), true),
            'preconnect' => $this->renderTag('link', array_merge([
                'rel' => 'preconnect',
                'href' => $url,
            ], $extraAttrs), true),
            'script' => $this->renderTag('script', array_merge([
                'src' => $url,
            ], $extraAttrs), false),
            'module' => $this->renderTag('script', array_merge([
                'type' => 'module',
                'src' => $url,
            ], $extraAttrs), false),
            'img' => $this->renderTag('img', array_merge([
                'src' => $url,
            ], $extraAttrs), true),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function renderTag(string $tag, array $attributes, bool $selfClosing): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                .'="'
                .htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
                .'"';
        }

        $attrString = implode(' ', $parts);

        if ($selfClosing) {
            return '<'.$tag.($attrString !== '' ? ' '.$attrString : '').' />';
        }

        return '<'.$tag.($attrString !== '' ? ' '.$attrString : '').'></'.$tag.'>';
    }
}
