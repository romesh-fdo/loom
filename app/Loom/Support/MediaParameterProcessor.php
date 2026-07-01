<?php

namespace Loom\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Loom\Support\ThemeContent\PageLayoutFieldsComposer;

class MediaParameterProcessor
{
    /**
     * @var list<string>
     */
    public const MEDIA_TYPES = ['media_selector', 'media_attach', 'media_finder'];

    public static function isMediaType(string $type): bool
    {
        return in_array($type, self::MEDIA_TYPES, true);
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    public static function resolveEffectiveType(array $parameter, mixed $value = null): string
    {
        $type = (string) ($parameter['type'] ?? 'text');

        if (UrlParameterProcessor::isUrlType($type)) {
            return UrlParameterProcessor::URL_TYPE;
        }

        if (self::isMediaType($type)) {
            return $type === 'media_finder' ? 'media_selector' : $type;
        }

        if ($type === 'file') {
            return 'media_attach';
        }

        $candidate = $value;

        if ($candidate === null || $candidate === '') {
            $candidate = $parameter['default'] ?? null;
        }

        if (is_array($candidate) && array_key_exists('url', $candidate)) {
            if (array_key_exists('alt', $candidate)) {
                return 'media_selector';
            }

            if (array_key_exists('id', $candidate) || array_key_exists('target', $candidate)) {
                return UrlParameterProcessor::URL_TYPE;
            }

            return 'media_selector';
        }

        return $type;
    }

    public static function isMediaCompoundValue(mixed $value): bool
    {
        return is_array($value) && array_key_exists('url', $value);
    }

    /**
     * @return array{url: string, alt: string, class: string}
     */
    public static function normalizeCompoundValue(mixed $value): array
    {
        if (is_string($value)) {
            return [
                'url' => $value,
                'alt' => '',
                'class' => '',
            ];
        }

        if (! is_array($value)) {
            return [
                'url' => '',
                'alt' => '',
                'class' => '',
            ];
        }

        return [
            'url' => is_string($value['url'] ?? null) ? $value['url'] : '',
            'alt' => is_string($value['alt'] ?? null) ? $value['alt'] : '',
            'class' => is_string($value['class'] ?? null) ? $value['class'] : '',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function processValues(array $parameters, array $values, Request $request, string $valuesPrefix): array
    {
        foreach ($parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $name = $parameter['name'] ?? null;
            $type = $parameter['type'] ?? 'text';

            if (! is_string($name) || $name === '' || (! self::isMediaType($type) && ! UrlParameterProcessor::isUrlType($type))) {
                continue;
            }

            if (UrlParameterProcessor::isUrlType($type)) {
                $values[$name] = UrlParameterProcessor::normalizeCompoundValue($values[$name] ?? []);

                continue;
            }

            $compound = self::normalizeCompoundValue($values[$name] ?? []);

            if ($type === 'media_attach') {
                $file = $request->file("{$valuesPrefix}.{$name}.file");

                if ($file instanceof UploadedFile && $file->isValid()) {
                    $storedPath = $file->store('', 'uploads');
                    $compound['url'] = Storage::disk('uploads')->url($storedPath);
                }
            }

            $values[$name] = $compound;
        }

        return $values;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @param  callable(string): list<array<string, mixed>>  $parametersForBlock
     * @return list<array<string, mixed>>
     */
    public function processSections(array $sections, Request $request, callable $parametersForBlock): array
    {
        foreach ($sections as $index => $section) {
            if (! is_array($section)) {
                continue;
            }

            $blockSlug = (string) ($section['block_slug'] ?? '');
            $parameters = $parametersForBlock($blockSlug);
            $values = is_array($section['values'] ?? null) ? $section['values'] : [];

            $sections[$index]['values'] = $this->processValues(
                $parameters,
                $values,
                $request,
                "sections.{$index}.values"
            );
        }

        return $sections;
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutFields
     * @param  callable(string): list<array<string, mixed>>  $parametersForSegment
     * @return array<string, array<string, mixed>>
     */
    public function processLayoutFields(array $layoutFields, Request $request, callable $parametersForSegment): array
    {
        foreach ($layoutFields as $segmentPath => $fields) {
            if (! is_string($segmentPath) || ! is_array($fields)) {
                continue;
            }

            $parameters = $parametersForSegment($segmentPath);
            $flatFields = [];

            foreach ($fields as $name => $value) {
                if (! is_string($name)) {
                    continue;
                }

                if ($name === '_mode') {
                    continue;
                }

                if (is_array($value) && PageLayoutFieldsComposer::isSubmittedDynamicField($value)) {
                    $flatFields[$name] = $value;

                    continue;
                }

                if (is_array($value) && isset($value['dynamic'])) {
                    $flatFields[$name] = $value;

                    continue;
                }

                if (is_array($value) && array_key_exists('static', $value)) {
                    $flatFields[$name] = $value['static'];

                    continue;
                }

                $flatFields[$name] = $value;
            }

            $layoutFields[$segmentPath] = $this->processValues(
                $parameters,
                $flatFields,
                $request,
                "layout_fields.{$segmentPath}"
            );
        }

        return $layoutFields;
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     */
    public static function validateCompoundValue(mixed $value, string $parameterName, Closure $fail): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value)) {
            return true;
        }

        if (! is_array($value)) {
            $fail('Parameter "'.$parameterName.'" must be a media object with url, alt, and class.');

            return false;
        }

        foreach (['url', 'alt', 'class'] as $key) {
            if (array_key_exists($key, $value) && $value[$key] !== null && ! is_string($value[$key])) {
                $fail('Parameter "'.$parameterName.'.'.$key.'" must be a string.');

                return false;
            }
        }

        return true;
    }
}
