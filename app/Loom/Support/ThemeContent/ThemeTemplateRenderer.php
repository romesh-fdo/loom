<?php

namespace Loom\Support\ThemeContent;

class ThemeTemplateRenderer
{
    /**
     * Replace scalar placeholders and expand Blade @foreach blocks.
     *
     * @param  list<array<string, mixed>>  $parameters
     */
    public static function render(
        string $template,
        array $values,
        string $prefix = 'blockData',
        array $parameters = [],
        ?ThemeRenderContext $context = null,
    ): string {
        $rawFields = self::rawFieldNames($parameters);
        $template = self::renderLoops($template, $values, $prefix);
        $template = self::renderScalars($template, $values, $prefix, $rawFields);

        return self::renderAssets($template, $context);
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     */
    public static function renderBlock(
        string $template,
        array $values,
        array $parameters = [],
        ?ThemeRenderContext $context = null,
    ): string {
        return self::render($template, $values, 'blockData', $parameters, $context);
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     */
    public static function renderSegment(
        string $template,
        array $values,
        array $parameters = [],
        ?ThemeRenderContext $context = null,
    ): string {
        return self::render($template, $values, 'segmentData', $parameters, $context);
    }

    private static function renderAssets(string $template, ?ThemeRenderContext $context): string
    {
        if ($context === null) {
            return $template;
        }

        return (new ThemeAssetsDirective)->render($template, $context);
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @return list<string>
     */
    private static function rawFieldNames(array $parameters): array
    {
        $names = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            if (($parameter['type'] ?? '') !== 'richtext') {
                continue;
            }

            $name = $parameter['name'] ?? null;

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private static function renderLoops(string $template, array $scope, string $dataPrefix): string
    {
        $pattern = '/@foreach\s*\(\s*\$'.preg_quote($dataPrefix, '/').'\[[\'"](\w+)[\'"]\]\s*\?\?\s*\[\]\s+as\s+\$(\w+)\s*\)/';

        if (! preg_match($pattern, $template, $match, PREG_OFFSET_CAPTURE)) {
            return $template;
        }

        $startPos = $match[0][1];
        $loopName = $match[1][0];
        $itemName = $match[2][0];
        $searchFrom = $startPos + strlen($match[0][0]);

        $endPos = strpos($template, '@endforeach', $searchFrom);

        if ($endPos === false) {
            return $template;
        }

        $innerStart = $searchFrom;

        if (isset($template[$innerStart]) && $template[$innerStart] === "\n") {
            $innerStart++;
        }

        $innerTemplate = substr($template, $innerStart, $endPos - $innerStart);
        $endTagEnd = $endPos + strlen('@endforeach');

        $rows = $scope[$loopName] ?? [];
        $rendered = '';

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $rowTemplate = self::renderLoops($innerTemplate, $row, $dataPrefix);
                $rendered .= self::renderItemPlaceholders($rowTemplate, $row, $itemName);
            }
        }

        $template = substr($template, 0, $startPos).$rendered.substr($template, $endTagEnd);

        return self::renderLoops($template, $scope, $dataPrefix);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function renderItemPlaceholders(string $template, array $row, string $itemName): string
    {
        $nestedPattern = '/\{\{\s*\$'.preg_quote($itemName, '/').'\[[\'"]([^\'"]+)[\'"]\]\[[\'"]([^\'"]+)[\'"]\]\s*\}\}/';

        $template = preg_replace_callback(
            $nestedPattern,
            static function (array $matches) use ($row): string {
                $field = $matches[1];
                $subKey = $matches[2];
                $value = $row[$field] ?? null;

                if (is_array($value)) {
                    return self::formatScalarValue($value[$subKey] ?? '');
                }

                return self::formatScalarValue($subKey === 'url' ? $value : '');
            },
            $template
        ) ?? $template;

        $pattern = '/\{\{\s*\$'.preg_quote($itemName, '/').'\[[\'"]([^\'"]+)[\'"]\]\s*\}\}/';

        return preg_replace_callback(
            $pattern,
            static function (array $matches) use ($row): string {
                return self::formatScalarValue($row[$matches[1]] ?? '');
            },
            $template
        ) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $rawFields
     */
    private static function renderScalars(string $template, array $values, string $prefix, array $rawFields = []): string
    {
        $nestedBracketPattern = '/\{\{\s*\$'.preg_quote($prefix, '/').'\[[\'"]([^\'"]+)[\'"]\]\[[\'"]([^\'"]+)[\'"]\]\s*\}\}/';

        $template = preg_replace_callback(
            $nestedBracketPattern,
            static function (array $matches) use ($values): string {
                $key = $matches[1];
                $subKey = $matches[2];
                $value = $values[$key] ?? null;

                if (is_array($value)) {
                    return self::formatScalarValue($value[$subKey] ?? '');
                }

                return self::formatScalarValue($subKey === 'url' ? $value : '');
            },
            $template
        ) ?? $template;

        $bracketPattern = '/\{\{\s*\$'.preg_quote($prefix, '/').'\[[\'"]([^\'"]+)[\'"]\]\s*\}\}/';

        $template = preg_replace_callback(
            $bracketPattern,
            static function (array $matches) use ($values, $rawFields): string {
                $key = $matches[1];

                return self::formatScalarValue(
                    $values[$key] ?? '',
                    in_array($key, $rawFields, true)
                );
            },
            $template
        ) ?? $template;

        $legacyPattern = '/\{\{\s*'.preg_quote($prefix, '/').'\.([a-z][a-z0-9_]*)\s*\}\}/';

        return preg_replace_callback(
            $legacyPattern,
            static function (array $matches) use ($values, $rawFields): string {
                $key = $matches[1];

                return self::formatScalarValue(
                    $values[$key] ?? '',
                    in_array($key, $rawFields, true)
                );
            },
            $template
        ) ?? $template;
    }

    private static function formatScalarValue(mixed $value, bool $raw = false): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return '';
        }

        if ($raw) {
            return (string) $value;
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
