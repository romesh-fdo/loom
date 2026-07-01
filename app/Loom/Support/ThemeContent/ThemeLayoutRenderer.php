<?php

namespace Loom\Support\ThemeContent;

use Loom\Support\ThemeManager;

class ThemeLayoutRenderer
{
    public function __construct(
        protected SegmentStore $segments,
        protected ThemeManager $themes,
    ) {}

    public function render(
        ThemeFileRecord $layout,
        string $content,
        ?string $themeSlug = null,
        ?ThemeFileRecord $page = null,
        array $bindings = [],
    ): string {
        $template = is_string($layout->code ?? null)
            ? $layout->code
            : (string) ($layout->code['template'] ?? '');

        $html = str_replace('{{ $content }}', $content, $template);

        return $this->expandSegments($html, $themeSlug, $page, $bindings);
    }

    protected function expandSegments(string $template, ?string $themeSlug, ?ThemeFileRecord $page, array $bindings = []): string
    {
        $pattern = "/@segment\s*\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*/";
        $offset = 0;

        while (preg_match($pattern, $template, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $fullStart = $match[0][1];
            $path = stripcslashes($match[1][0]);
            $arrayStart = $fullStart + strlen($match[0][0]);
            $arrayLiteral = $this->extractParamsLiteral($template, $arrayStart);
            $arrayEnd = $arrayStart + strlen($arrayLiteral);
            $closePos = $arrayEnd;

            while (isset($template[$closePos]) && ctype_space($template[$closePos])) {
                $closePos++;
            }

            if (! isset($template[$closePos]) || $template[$closePos] !== ')') {
                $offset = $arrayEnd;

                continue;
            }

            $directiveEnd = $closePos + 1;
            $replacement = $this->renderSegmentDirective($path, $arrayStart, $template, $themeSlug, $page, $bindings);
            $template = substr($template, 0, $fullStart).$replacement.substr($template, $directiveEnd);
            $offset = $fullStart + strlen($replacement);
        }

        return $template;
    }

    protected function renderSegmentDirective(
        string $path,
        int $arrayStart,
        string $template,
        ?string $themeSlug,
        ?ThemeFileRecord $page,
        array $bindings = [],
    ): string {
        $path = stripcslashes($path);
        $segment = $this->segments->find($path, $themeSlug);

        if ($segment === null || ! ($segment->enabled ?? true)) {
            return '';
        }

        $paramsLiteral = $this->extractParamsLiteral($template, $arrayStart);
        $code = is_array($segment->code ?? null) ? $segment->code : [];
        $segmentTemplate = (string) ($code['template'] ?? '');
        $parameters = is_array($code['parameters'] ?? null) ? $code['parameters'] : [];
        $inlineValues = ThemeDirectiveParser::parseInlineParams($paramsLiteral);
        $layoutFields = is_array($page?->layout_fields) ? $page->layout_fields : [];
        $themeSlug ??= $this->themes->activeSlug();
        $context = new ThemeRenderContext($themeSlug, $bindings);
        $pageValues = LayoutFieldResolver::resolveForSegment($layoutFields, $path, $context);
        $values = $this->mergeSegmentValues($parameters, $inlineValues, $pageValues);

        return ThemeTemplateRenderer::renderSegment(
            $segmentTemplate,
            $values,
            $parameters,
            $context,
        );
    }

    protected function extractParamsLiteral(string $template, int $arrayStart): string
    {
        $length = strlen($template);

        if (! isset($template[$arrayStart]) || $template[$arrayStart] !== '[') {
            return '[]';
        }

        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($index = $arrayStart; $index < $length; $index++) {
            $char = $template[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($char === "'") {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'") {
                $inString = true;

                continue;
            }

            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                $depth--;

                if ($depth === 0) {
                    return substr($template, $arrayStart, $index - $arrayStart + 1);
                }
            }
        }

        return '[]';
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @param  array<string, mixed>  $inlineValues
     * @param  array<string, mixed>  $pageValues
     * @return array<string, mixed>
     */
    protected function mergeSegmentValues(array $parameters, array $inlineValues, array $pageValues): array
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

            if (array_key_exists($name, $pageValues)) {
                $values[$name] = $pageValues[$name];
            } elseif (array_key_exists($name, $inlineValues)) {
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

        foreach ($pageValues as $key => $value) {
            if (! array_key_exists($key, $values)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
