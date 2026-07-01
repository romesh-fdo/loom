<?php

namespace Loom\Support\ThemeContent;

class PageEntityImportsComposer
{
    /**
     * @param  list<array<string, mixed>>  $entityImports
     * @return list<string>
     */
    public static function toPhpLines(array $entityImports): array
    {
        $lines = [];

        foreach ($entityImports as $import) {
            if (! is_array($import)) {
                continue;
            }

            $variable = self::normalizeVariableName((string) ($import['variable'] ?? ''));
            $plugin = trim((string) ($import['plugin'] ?? ''));
            $function = trim((string) ($import['function'] ?? ''));

            if ($variable === '' || $plugin === '' || $function === '') {
                continue;
            }

            $parameters = is_array($import['parameters'] ?? null) ? $import['parameters'] : [];
            $argsLiteral = self::formatParametersArray($parameters);

            $lines[] = '    $'.$variable.' = loom_import(\''
                .self::escapeSingleQuoted($plugin).'\', \''
                .self::escapeSingleQuoted($function).'\', '.$argsLiteral.');';
        }

        return $lines;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fromPhpBlock(string $phpBlock): array
    {
        $inner = PageLayoutFieldsComposer::unwrapPhpBlockInner($phpBlock);
        $imports = [];
        $pattern = '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*loom_import\s*\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*,\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*,\s*(\[[\s\S]*?\])\s*\)\s*;/';

        if (! preg_match_all($pattern, $inner, $matches, PREG_SET_ORDER)) {
            return $imports;
        }

        foreach ($matches as $match) {
            $parameters = self::parseParametersArray($match[4]);

            $imports[] = [
                'variable' => $match[1],
                'plugin' => stripcslashes($match[2]),
                'function' => stripcslashes($match[3]),
                'parameters' => $parameters,
            ];
        }

        return $imports;
    }

    public static function isValidVariableName(string $name): bool
    {
        return PageLayoutFieldsComposer::isValidVariableName($name);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function formatParametersArray(array $parameters): string
    {
        if ($parameters === []) {
            return '[]';
        }

        $pairs = [];

        foreach ($parameters as $name => $binding) {
            if (! is_string($name) || $name === '' || ! is_array($binding)) {
                continue;
            }

            $pairs[] = '\''.self::escapeSingleQuoted($name).'\' => '.self::formatParameterBinding($binding);
        }

        return '['.implode(', ', $pairs).']';
    }

    /**
     * @param  array<string, mixed>  $binding
     */
    private static function formatParameterBinding(array $binding): string
    {
        $mode = (string) ($binding['mode'] ?? 'static');

        return match ($mode) {
            'path_param' => 'request()->route('.self::formatRouteParamName((string) ($binding['param'] ?? '')).')',
            'query_param' => 'request()->query('.self::formatRouteParamName((string) ($binding['param'] ?? '')).')',
            'url_segment' => 'request()->segment('.max(1, (int) ($binding['segment'] ?? 1)).')',
            'route_param' => 'request()->route('.self::formatRouteParamName((string) ($binding['param'] ?? '')).')',
            default => '\''.self::escapeSingleQuoted((string) ($binding['value'] ?? '')).'\'',
        };
    }

    private static function formatRouteParamName(string $param): string
    {
        if ($param === '') {
            return "''";
        }

        return '\''.self::escapeSingleQuoted($param).'\'';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function parseParametersArray(string $literal): array
    {
        $literal = trim($literal);

        if ($literal === '[]') {
            return [];
        }

        $inner = trim(substr($literal, 1, -1));
        $parameters = [];
        $offset = 0;
        $length = strlen($inner);

        while ($offset < $length) {
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset >= $length || $inner[$offset] !== "'") {
                break;
            }

            $keyResult = self::readQuotedString($inner, $offset);
            $key = $keyResult['value'];
            $offset = $keyResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if (! str_starts_with(substr($inner, $offset), '=>')) {
                break;
            }

            $offset += 2;
            $offset = self::skipWhitespace($inner, $offset);
            $valueResult = self::readExpression($inner, $offset);
            $parameters[$key] = self::parseParameterValue($valueResult['value']);
            $offset = $valueResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset < $length && $inner[$offset] === ',') {
                $offset++;
            }
        }

        return $parameters;
    }

    /**
     * @return array{value: string, offset: int}
     */
    private static function readExpression(string $inner, int $offset): array
    {
        $offset = self::skipWhitespace($inner, $offset);
        $start = $offset;
        $length = strlen($inner);
        $depth = 0;
        $inString = false;
        $escaped = false;

        while ($offset < $length) {
            $char = $inner[$offset];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    $offset++;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    $offset++;

                    continue;
                }

                if ($char === "'") {
                    $inString = false;
                    $offset++;

                    continue;
                }

                $offset++;

                continue;
            }

            if ($char === "'") {
                $inString = true;
                $offset++;

                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
                $offset++;

                continue;
            }

            if ($char === ')' || $char === ']') {
                if ($depth > 0) {
                    $depth--;
                }

                $offset++;

                continue;
            }

            if ($depth === 0 && ($char === ',' || $char === ']')) {
                break;
            }

            $offset++;
        }

        return [
            'value' => trim(substr($inner, $start, $offset - $start)),
            'offset' => $offset,
        ];
    }

    /**
     * @return array{value: string, offset: int}
     */
    private static function readQuotedString(string $inner, int $offset): array
    {
        $length = strlen($inner);
        $value = '';
        $offset++;

        while ($offset < $length) {
            $char = $inner[$offset];

            if ($char === '\\' && $offset + 1 < $length) {
                $value .= $inner[$offset + 1];
                $offset += 2;

                continue;
            }

            if ($char === "'") {
                return ['value' => $value, 'offset' => $offset + 1];
            }

            $value .= $char;
            $offset++;
        }

        return ['value' => $value, 'offset' => $offset];
    }

    private static function skipWhitespace(string $value, int $offset): int
    {
        return $offset + strspn($value, " \t\r\n", $offset);
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseParameterValue(mixed $value): array
    {
        if (! is_string($value)) {
            return ['mode' => 'static', 'value' => is_scalar($value) ? (string) $value : ''];
        }

        if (preg_match("/^'((?:[^'\\\\]|\\\\.)*)'$/", $value, $match)) {
            return ['mode' => 'static', 'value' => stripcslashes($match[1])];
        }

        if (preg_match('/^request\(\)->segment\((\d+)\)$/', $value, $match)) {
            return ['mode' => 'url_segment', 'segment' => (int) $match[1]];
        }

        if (preg_match('/^request\(\)->query\(\'((?:[^\'\\\\]|\\\\.)*)\'\)$/', $value, $match)) {
            return ['mode' => 'query_param', 'param' => stripcslashes($match[1])];
        }

        if (preg_match('/^request\(\)->route\(\'((?:[^\'\\\\]|\\\\.)*)\'\)$/', $value, $match)) {
            return ['mode' => 'path_param', 'param' => stripcslashes($match[1])];
        }

        return ['mode' => 'static', 'value' => $value];
    }

    private static function normalizeVariableName(string $name): string
    {
        $name = ltrim(trim($name), '$');

        return self::isValidVariableName($name) ? $name : '';
    }

    private static function escapeSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
