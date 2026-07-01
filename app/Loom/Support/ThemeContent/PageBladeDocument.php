<?php

namespace Loom\Support\ThemeContent;

class PageBladeDocument
{
    public const META_MARKER = 'loom:meta';

    /**
     * @return array{meta: array<string, mixed>, entity_imports: list<array<string, mixed>>, layout_fields: array<string, array<string, mixed>>, template: string}
     */
    public static function parse(string $contents): array
    {
        $contents = ltrim($contents);
        $meta = [];
        $entityImports = [];
        $layoutFields = [];
        $remainder = $contents;

        if (str_starts_with($contents, '{{--')) {
            $end = strpos($contents, '--}}');

            if ($end !== false) {
                $comment = substr($contents, 0, $end + 4);
                $remainder = ltrim(substr($contents, $end + 4));

                if (preg_match('/\{\{--\s*'.preg_quote(self::META_MARKER, '/').'\s*(.*?)--\}\}/s', $comment, $matches)) {
                    $decoded = json_decode(trim($matches[1]), true);
                    $meta = is_array($decoded) ? $decoded : [];
                }
            }
        }

        $phpBlock = '';
        $template = $remainder;

        if (preg_match('/^@php\s*\r?\n.*?\r?\n@endphp\s*\r?\n?/s', $remainder, $matches, PREG_OFFSET_CAPTURE)) {
            $phpBlock = trim($matches[0][0]);
            $template = ltrim(substr($remainder, $matches[0][1] + strlen($matches[0][0])));
        }

        if ($phpBlock !== '') {
            $entityImports = PageEntityImportsComposer::fromPhpBlock($phpBlock);
            $layoutFields = PageLayoutFieldsComposer::fromPhpBlock($phpBlock);
        }

        if ($layoutFields === [] && is_array($meta['layout_fields'] ?? null)) {
            $layoutFields = self::normalizeLegacyLayoutFields($meta['layout_fields']);
        }

        if ($entityImports === [] && is_array($meta['entity_imports'] ?? null)) {
            $entityImports = array_values(array_filter($meta['entity_imports'], 'is_array'));
        }

        unset($meta['layout_fields'], $meta['entity_imports']);

        return [
            'meta' => $meta,
            'entity_imports' => $entityImports,
            'layout_fields' => $layoutFields,
            'template' => self::unwrapVerbatim($template),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, mixed>>  $entityImports
     * @param  array<string, array<string, mixed>>  $layoutFields
     */
    public static function compose(array $meta, array $entityImports, array $layoutFields, string $template): string
    {
        unset($meta['layout_fields'], $meta['entity_imports']);

        $metaJson = json_encode(
            $meta,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $parts = [
            '{{-- '.self::META_MARKER.PHP_EOL.$metaJson.PHP_EOL.'--}}',
        ];

        $importLines = PageEntityImportsComposer::toPhpLines($entityImports);
        $phpBlock = PageLayoutFieldsComposer::composePhpBlock($importLines, $layoutFields);

        if ($phpBlock !== '') {
            $parts[] = $phpBlock;
        }

        $body = trim($template) === '' ? '' : self::wrapVerbatim($template);

        if ($body !== '') {
            $parts[] = $body;
        }

        return implode(PHP_EOL.PHP_EOL, $parts).PHP_EOL;
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, array<string, mixed>>
     */
    private static function normalizeLegacyLayoutFields(array $legacy): array
    {
        $normalized = [];

        foreach ($legacy as $segmentPath => $fields) {
            if (! is_string($segmentPath) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $fieldName => $value) {
                if (! is_string($fieldName)) {
                    continue;
                }

                if (PageLayoutFieldsComposer::isDynamicField($value) && is_array($value)) {
                    $normalized[$segmentPath][$fieldName] = PageLayoutFieldsComposer::normalizeDynamicField($value);

                    continue;
                }

                if (is_array($value) && isset($value['dynamic']) && is_string($value['dynamic'])) {
                    $normalized[$segmentPath][$fieldName] = PageLayoutFieldsComposer::normalizeDynamicField($value);

                    continue;
                }

                $normalized[$segmentPath][$fieldName] = $value;
            }
        }

        return $normalized;
    }

    protected static function wrapVerbatim(string $template): string
    {
        $trimmed = rtrim($template, "\r\n");

        if ($trimmed === '') {
            return '';
        }

        return "@verbatim\n{$trimmed}\n@endverbatim";
    }

    protected static function unwrapVerbatim(string $template): string
    {
        $trimmed = trim($template);

        if (preg_match('/^@verbatim\s*\r?\n(.*?)\r?\n@endverbatim\s*$/s', $trimmed, $matches)) {
            return $matches[1];
        }

        return $template;
    }
}
