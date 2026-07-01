<?php

namespace Loom\Support\ThemeContent;

class ThemeDirectiveParser
{
    /**
     * @return list<array{path: string, params: array<string, mixed>}>
     */
    public static function parseSegmentDirectives(string $template): array
    {
        return self::parseDirectives($template, 'segment', 'path');
    }

    /**
     * @return list<array{blockSlug: string, values: array<string, mixed>}>
     */
    public static function parseBlockDirectives(string $template): array
    {
        $directives = self::parseDirectives($template, 'block', 'blockSlug');

        return array_map(
            fn (array $directive) => [
                'blockSlug' => $directive['blockSlug'],
                'values' => $directive['params'],
            ],
            $directives
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function formatBlockDirective(string $slug, array $values): string
    {
        return self::formatDirective('block', $slug, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function formatSegmentDirective(string $path, array $values): string
    {
        return self::formatDirective('segment', $path, $values);
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseInlineParams(string $literal): array
    {
        $literal = trim($literal);

        if ($literal === '[]') {
            return [];
        }

        if (! str_starts_with($literal, '[')) {
            return [];
        }

        return self::parsePhpAssocArray($literal);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function formatPhpAssocArray(array $values): string
    {
        if ($values === []) {
            return '[]';
        }

        $pairs = [];

        foreach ($values as $key => $value) {
            $pairs[] = "'".self::escapePhpSingleQuoted((string) $key)."' => ".self::formatPhpValue($value);
        }

        return '['.implode(', ', $pairs).']';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function parseDirectives(string $template, string $directiveName, string $identifierKey): array
    {
        $results = [];
        $pattern = '/@'.preg_quote($directiveName, '/').'\s*\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*,\s*/';
        $offset = 0;

        while (preg_match($pattern, $template, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $identifier = stripcslashes($match[1][0]);
            $arrayStart = $match[0][1] + strlen($match[0][0]);
            $arrayLiteral = self::extractBalancedBrackets($template, $arrayStart);

            if ($arrayLiteral === null) {
                break;
            }

            $results[] = [
                $identifierKey => $identifier,
                'params' => self::parsePhpAssocArray($arrayLiteral),
            ];

            $offset = $arrayStart + strlen($arrayLiteral);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function formatDirective(string $name, string $identifier, array $values): string
    {
        $args = self::formatPhpAssocArray($values);

        return "@{$name}('".self::escapePhpSingleQuoted($identifier)."', {$args})";
    }

    private static function extractBalancedBrackets(string $template, int $start): ?string
    {
        if (! isset($template[$start]) || $template[$start] !== '[') {
            return null;
        }

        $depth = 0;
        $length = strlen($template);
        $inString = false;
        $escaped = false;

        for ($index = $start; $index < $length; $index++) {
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
                    return substr($template, $start, $index - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parsePhpAssocArray(string $literal): array
    {
        $literal = trim($literal);

        if ($literal === '[]') {
            return [];
        }

        $inner = trim(substr($literal, 1, -1));

        if ($inner === '') {
            return [];
        }

        $params = [];
        $offset = 0;
        $length = strlen($inner);

        while ($offset < $length) {
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset >= $length) {
                break;
            }

            if ($inner[$offset] !== "'") {
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
            $valueResult = self::readValue($inner, $offset);
            $params[$key] = $valueResult['value'];
            $offset = $valueResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset < $length && $inner[$offset] === ',') {
                $offset++;
            }
        }

        return $params;
    }

    /**
     * @return array{value: mixed, offset: int}
     */
    private static function readValue(string $inner, int $offset): array
    {
        $offset = self::skipWhitespace($inner, $offset);

        if ($offset >= strlen($inner)) {
            return ['value' => null, 'offset' => $offset];
        }

        $char = $inner[$offset];

        if ($char === "'") {
            $result = self::readQuotedString($inner, $offset);

            return ['value' => $result['value'], 'offset' => $result['offset']];
        }

        if ($char === '[') {
            $literal = self::extractBalancedBrackets($inner, $offset);

            if ($literal === null) {
                return ['value' => [], 'offset' => $offset];
            }

            return [
                'value' => self::parsePhpAssocArray($literal),
                'offset' => $offset + strlen($literal),
            ];
        }

        if (preg_match('/\G(true|false|null|-?\d+(?:\.\d+)?)/', $inner, $match, 0, $offset)) {
            return [
                'value' => self::parseScalarToken($match[1]),
                'offset' => $offset + strlen($match[1]),
            ];
        }

        return ['value' => null, 'offset' => $offset];
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

    private static function parseScalarToken(string $token): mixed
    {
        if ($token === 'true') {
            return true;
        }

        if ($token === 'false') {
            return false;
        }

        if ($token === 'null') {
            return null;
        }

        if (is_numeric($token)) {
            return str_contains($token, '.') ? (float) $token : (int) $token;
        }

        return $token;
    }

    private static function formatPhpValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return self::formatPhpAssocArray($value);
        }

        return "'".self::escapePhpSingleQuoted((string) $value)."'";
    }

    private static function escapePhpSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
