<?php

namespace Loom\Support\ThemeContent;

use InvalidArgumentException;

class ThemeBladeDocument
{
    public const META_MARKER = 'loom:meta';

    /**
     * @return array{meta: array<string, mixed>, template: string}
     */
    public static function parse(string $contents): array
    {
        $contents = ltrim($contents);

        if (! str_starts_with($contents, '{{--')) {
            return [
                'meta' => [],
                'template' => $contents,
            ];
        }

        $end = strpos($contents, '--}}');

        if ($end === false) {
            return [
                'meta' => [],
                'template' => $contents,
            ];
        }

        $comment = substr($contents, 0, $end + 4);
        $template = ltrim(substr($contents, $end + 4));

        if (! preg_match('/\{\{--\s*'.preg_quote(self::META_MARKER, '/').'\s*(.*?)--\}\}/s', $comment, $matches)) {
            return [
                'meta' => [],
                'template' => self::unwrapVerbatim($template),
            ];
        }

        $meta = json_decode(trim($matches[1]), true);

        if (! is_array($meta)) {
            throw new InvalidArgumentException('Invalid loom meta block in blade file.');
        }

        return [
            'meta' => $meta,
            'template' => self::unwrapVerbatim($template),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function compose(array $meta, string $template): string
    {
        $metaJson = json_encode(
            $meta,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $body = trim($template) === '' ? '' : self::wrapVerbatim($template);

        return '{{-- '.self::META_MARKER.PHP_EOL
            .$metaJson.PHP_EOL
            .'--}}'.PHP_EOL.PHP_EOL
            .$body.PHP_EOL;
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
