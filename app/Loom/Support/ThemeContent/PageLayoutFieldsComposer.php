<?php

namespace Loom\Support\ThemeContent;

class PageLayoutFieldsComposer
{
    /**
     * @param  array<string, array<string, mixed>>  $layoutFields
     */
    public static function toPhpBlock(array $layoutFields): string
    {
        if ($layoutFields === []) {
            return '';
        }

        $arrayValues = [];

        foreach ($layoutFields as $segmentPath => $fields) {
            if (! is_string($segmentPath) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $fieldName => $value) {
                if (! is_string($fieldName) || $fieldName === '') {
                    continue;
                }

                if (self::isImportDynamicField($value)) {
                    $import = trim((string) ($value['import'] ?? ''));
                    $field = trim((string) ($value['field'] ?? ''));

                    if ($import === '' || $field === '') {
                        continue;
                    }

                    $arrayValues[$segmentPath][$fieldName] = self::formatDynamicExpression($import, $field);

                    continue;
                }

                if (self::isLegacyDynamicField($value)) {
                    $import = self::splitDynamicPath((string) $value['dynamic'])[0] ?? '';
                    $field = self::splitDynamicPath((string) $value['dynamic'])[1] ?? '';

                    if ($import !== '' && $field !== '') {
                        $arrayValues[$segmentPath][$fieldName] = self::formatDynamicExpression($import, $field);
                    }

                    continue;
                }

                $arrayValues[$segmentPath][$fieldName] = $value;
            }
        }

        if ($arrayValues === []) {
            return '';
        }

        $lines = [
            '    $layoutFields = '.self::formatLayoutFieldsArray($arrayValues).';',
        ];

        return "@php\n".implode(PHP_EOL, $lines)."\n@endphp";
    }

    /**
     * @param  list<string>  $importLines
     * @param  array<string, array<string, mixed>>  $layoutFields
     */
    public static function composePhpBlock(array $importLines, array $layoutFields): string
    {
        $layoutBlock = self::toPhpBlock($layoutFields);
        $layoutLines = [];

        if ($layoutBlock !== '') {
            $inner = self::unwrapPhpBlockInner($layoutBlock);
            $layoutLines = array_filter(explode("\n", $inner));
        }

        if ($importLines === [] && $layoutLines === []) {
            return '';
        }

        $lines = $importLines;

        if ($importLines !== [] && $layoutLines !== []) {
            $lines[] = '';
        }

        $lines = array_merge($lines, $layoutLines);

        return "@php\n".implode(PHP_EOL, $lines)."\n@endphp";
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fromPhpBlock(string $phpBlock): array
    {
        $phpBlock = trim($phpBlock);

        if ($phpBlock === '') {
            return [];
        }

        $inner = self::unwrapPhpBlockInner($phpBlock);
        $arrayLiteral = self::extractLayoutFieldsLiteral($inner);

        if ($arrayLiteral === null) {
            return [];
        }

        return self::parseLayoutFieldsArrayWithVariables($arrayLiteral);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function isDynamicField(mixed $value): bool
    {
        return self::isImportDynamicField($value) || self::isLegacyDynamicField($value);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function isImportDynamicField(mixed $value): bool
    {
        return is_array($value)
            && isset($value['import'], $value['field'])
            && is_string($value['import'])
            && is_string($value['field'])
            && trim($value['import']) !== ''
            && trim($value['field']) !== '';
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function isLegacyDynamicField(mixed $value): bool
    {
        return is_array($value)
            && isset($value['dynamic'])
            && is_string($value['dynamic'])
            && trim($value['dynamic']) !== '';
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function isSubmittedDynamicField(array $value): bool
    {
        if ((string) ($value['_mode'] ?? '') === 'dynamic') {
            return true;
        }

        if ((string) ($value['_mode'] ?? '') === 'static') {
            return false;
        }

        if (trim((string) ($value['import'] ?? '')) !== '') {
            return true;
        }

        return trim((string) ($value['dynamic'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array{import: string, field: string, dynamic: string}
     */
    public static function normalizeDynamicField(array $value): array
    {
        if (self::isImportDynamicField($value)) {
            $import = trim((string) $value['import']);
            $field = trim((string) $value['field']);

            return [
                'import' => $import,
                'field' => $field,
                'dynamic' => $import.'.'.$field,
            ];
        }

        $dynamic = trim((string) ($value['dynamic'] ?? ''));
        [$import, $field] = self::splitDynamicPath($dynamic);

        return [
            'import' => $import,
            'field' => $field,
            'dynamic' => $dynamic,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitDynamicPath(string $path): array
    {
        $segments = explode('.', $path, 2);

        if (count($segments) < 2) {
            return ['', ''];
        }

        return [$segments[0], $segments[1]];
    }

    public static function isValidVariableName(string $name): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 1;
    }

    public static function unwrapPhpBlockInner(string $phpBlock): string
    {
        $trimmed = trim($phpBlock);

        if (preg_match('/^@php\s*\r?\n(.*?)\r?\n@endphp\s*$/s', $trimmed, $matches)) {
            return $matches[1];
        }

        return $trimmed;
    }

    private static function formatDynamicExpression(string $import, string $field): string
    {
        return '($'.$import.' && isset($'.$import.'->'.$field.')) ? $'.$import.'->'.$field." : ''";
    }

    private static function extractLayoutFieldsLiteral(string $inner): ?string
    {
        if (! preg_match('/\$layoutFields\s*=\s*/', $inner, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $match[0][1] + strlen($match[0][0]);

        return self::extractBalancedBrackets($inner, $start);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function parseLayoutFieldsArrayWithVariables(string $literal): array
    {
        $literal = trim($literal);

        if ($literal === '[]') {
            return [];
        }

        $inner = trim(substr($literal, 1, -1));
        $layoutFields = [];
        $offset = 0;
        $length = strlen($inner);

        while ($offset < $length) {
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset >= $length || $inner[$offset] !== "'") {
                break;
            }

            $segmentResult = self::readQuotedString($inner, $offset);
            $segmentPath = $segmentResult['value'];
            $offset = $segmentResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if (! str_starts_with(substr($inner, $offset), '=>')) {
                break;
            }

            $offset += 2;
            $offset = self::skipWhitespace($inner, $offset);

            if (! isset($inner[$offset]) || $inner[$offset] !== '[') {
                break;
            }

            $segmentLiteral = self::extractBalancedBrackets($inner, $offset);

            if ($segmentLiteral === null) {
                break;
            }

            $layoutFields[$segmentPath] = self::parseSegmentFields($segmentLiteral);
            $offset += strlen($segmentLiteral);
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset < $length && $inner[$offset] === ',') {
                $offset++;
            }
        }

        return $layoutFields;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseSegmentFields(string $literal): array
    {
        $inner = trim(substr($literal, 1, -1));
        $fields = [];
        $offset = 0;
        $length = strlen($inner);

        while ($offset < $length) {
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset >= $length || $inner[$offset] !== "'") {
                break;
            }

            $fieldResult = self::readQuotedString($inner, $offset);
            $fieldName = $fieldResult['value'];
            $offset = $fieldResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if (! str_starts_with(substr($inner, $offset), '=>')) {
                break;
            }

            $offset += 2;
            $offset = self::skipWhitespace($inner, $offset);
            $valueResult = self::readFieldValue($inner, $offset);
            $fields[$fieldName] = self::normalizeParsedFieldValue($valueResult['value']);
            $offset = $valueResult['offset'];
            $offset = self::skipWhitespace($inner, $offset);

            if ($offset < $length && $inner[$offset] === ',') {
                $offset++;
            }
        }

        return $fields;
    }

    private static function normalizeParsedFieldValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^\(\$([a-zA-Z_][a-zA-Z0-9_]*)\s+&&\s+isset\(\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\)\)\s+\?\s+\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\s+:\s+\'\'$/', $value, $match)) {
            return [
                'import' => $match[1],
                'field' => $match[3],
                'dynamic' => $match[1].'.'.$match[3],
            ];
        }

        return $value;
    }

    /**
     * @return array{value: mixed, offset: int}
     */
    private static function readFieldValue(string $inner, int $offset): array
    {
        $offset = self::skipWhitespace($inner, $offset);

        if ($offset >= strlen($inner)) {
            return ['value' => null, 'offset' => $offset];
        }

        if ($inner[$offset] === '(') {
            $expression = self::readBalancedParentheses($inner, $offset);

            if ($expression !== null) {
                return ['value' => $expression, 'offset' => $offset + strlen($expression)];
            }
        }

        if ($inner[$offset] === '$') {
            if (preg_match('/\G\$([a-zA-Z_][a-zA-Z0-9_]*)/', $inner, $match, 0, $offset)) {
                return [
                    'value' => '$'.$match[1],
                    'offset' => $offset + strlen($match[0]),
                ];
            }
        }

        if ($inner[$offset] === "'") {
            $result = self::readQuotedString($inner, $offset);

            return ['value' => $result['value'], 'offset' => $result['offset']];
        }

        if ($inner[$offset] === '[') {
            $literal = self::extractBalancedBrackets($inner, $offset);

            if ($literal === null) {
                return ['value' => [], 'offset' => $offset];
            }

            return [
                'value' => ThemeDirectiveParser::parseInlineParams($literal),
                'offset' => $offset + strlen($literal),
            ];
        }

        if (preg_match('/\G(true|false|null|-?\d+(?:\.\d+)?)/', $inner, $match, 0, $offset)) {
            $token = $match[1];

            return [
                'value' => match ($token) {
                    'true' => true,
                    'false' => false,
                    'null' => null,
                    default => is_numeric($token)
                        ? (str_contains($token, '.') ? (float) $token : (int) $token)
                        : $token,
                },
                'offset' => $offset + strlen($token),
            ];
        }

        return ['value' => null, 'offset' => $offset];
    }

    private static function readBalancedParentheses(string $inner, int $offset): ?string
    {
        if (! isset($inner[$offset]) || $inner[$offset] !== '(') {
            return null;
        }

        $depth = 0;
        $length = strlen($inner);
        $inString = false;
        $escaped = false;

        for ($index = $offset; $index < $length; $index++) {
            $char = $inner[$index];

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

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    $segment = substr($inner, $offset, $index - $offset + 1);
                    $remainder = substr($inner, $index + 1);

                    if (preg_match('/^\s+\?\s+.*?\s+:\s+\'\'/', $remainder, $match)) {
                        return $segment.$match[0];
                    }

                    return $segment;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $arrayValues
     */
    private static function formatLayoutFieldsArray(array $arrayValues): string
    {
        $segmentPairs = [];

        foreach ($arrayValues as $segmentPath => $fields) {
            $fieldPairs = [];

            foreach ($fields as $fieldName => $value) {
                $fieldPairs[] = "'".self::escapeSingleQuoted((string) $fieldName)."' => ".self::formatFieldValue($value);
            }

            $segmentPairs[] = "'".self::escapeSingleQuoted((string) $segmentPath)."' => [".implode(', ', $fieldPairs).']';
        }

        return '['.implode(', ', $segmentPairs).']';
    }

    private static function formatFieldValue(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, '(')) {
            return $value;
        }

        if (is_string($value) && str_starts_with($value, '$')) {
            return $value;
        }

        if (is_array($value)) {
            return ThemeDirectiveParser::formatPhpAssocArray($value);
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".self::escapeSingleQuoted((string) $value)."'";
    }

    private static function escapeSingleQuoted(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
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
}
