<?php

namespace Loom\Support\ThemeContent;

use Loom\Support\ThemeManager;

class ThemeLayoutRenderer
{
    public function __construct(
        protected SegmentStore $segments,
        protected ThemeManager $themes,
    ) {}

    public function render(ThemeFileRecord $layout, string $content, ?string $themeSlug = null): string
    {
        $template = is_string($layout->code ?? null)
            ? $layout->code
            : (string) ($layout->code['template'] ?? '');

        $html = str_replace('{{ $content }}', $content, $template);

        return $this->expandSegments($html, $themeSlug);
    }

    protected function expandSegments(string $template, ?string $themeSlug): string
    {
        $pattern = "/@segment\s*\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*(\[[^\]]*\])\s*\)/";

        return preg_replace_callback(
            $pattern,
            fn (array $matches) => $this->renderSegmentDirective($matches[1], $matches[2], $themeSlug),
            $template
        ) ?? $template;
    }

    protected function renderSegmentDirective(string $path, string $paramsLiteral, ?string $themeSlug): string
    {
        $path = stripcslashes($path);
        $segment = $this->segments->find($path, $themeSlug);

        if ($segment === null || ! ($segment->enabled ?? true)) {
            return '';
        }

        $code = is_array($segment->code ?? null) ? $segment->code : [];
        $segmentTemplate = (string) ($code['template'] ?? '');
        $parameters = is_array($code['parameters'] ?? null) ? $code['parameters'] : [];
        $values = $this->mergeSegmentValues($parameters, $this->parseInlineParams($paramsLiteral));

        $themeSlug ??= $this->themes->activeSlug();

        return ThemeTemplateRenderer::renderSegment(
            $segmentTemplate,
            $values,
            $parameters,
            new ThemeRenderContext($themeSlug),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @param  array<string, mixed>  $inlineValues
     * @return array<string, mixed>
     */
    protected function mergeSegmentValues(array $parameters, array $inlineValues): array
    {
        $values = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $name = $parameter['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            if (array_key_exists($name, $inlineValues)) {
                $values[$name] = $inlineValues[$name];
            } elseif (array_key_exists('default', $parameter)) {
                $values[$name] = $parameter['default'];
            }
        }

        foreach ($inlineValues as $key => $value) {
            if (! array_key_exists($key, $values)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseInlineParams(string $literal): array
    {
        $literal = trim($literal);

        if ($literal === '[]') {
            return [];
        }

        $inner = trim($literal, '[]');
        $params = [];

        if ($inner === '') {
            return $params;
        }

        $pattern = "/'((?:[^'\\\\]|\\\\.)*)'\s*=>\s*(true|false|null|-?\d+(?:\.\d+)?|'(?:[^'\\\\]|\\\\.)*')/";

        if (! preg_match_all($pattern, $inner, $matches, PREG_SET_ORDER)) {
            return $params;
        }

        foreach ($matches as $match) {
            $key = stripcslashes($match[1]);
            $params[$key] = $this->parseInlineValue($match[2]);
        }

        return $params;
    }

    protected function parseInlineValue(string $raw): mixed
    {
        if ($raw === 'true') {
            return true;
        }

        if ($raw === 'false') {
            return false;
        }

        if ($raw === 'null') {
            return null;
        }

        if (is_numeric($raw)) {
            return str_contains($raw, '.') ? (float) $raw : (int) $raw;
        }

        if (str_starts_with($raw, "'") && str_ends_with($raw, "'")) {
            return stripcslashes(substr($raw, 1, -1));
        }

        return $raw;
    }
}
