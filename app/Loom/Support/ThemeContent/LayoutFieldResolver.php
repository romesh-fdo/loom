<?php

namespace Loom\Support\ThemeContent;

class LayoutFieldResolver
{
    /**
     * @param  array<string, mixed>  $layoutFields
     * @return array<string, mixed>
     */
    public static function resolveForSegment(array $layoutFields, string $segmentPath, ThemeRenderContext $context): array
    {
        $segmentFields = $layoutFields[$segmentPath] ?? null;

        if (! is_array($segmentFields)) {
            return [];
        }

        $resolved = [];

        foreach ($segmentFields as $name => $value) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            $resolved[$name] = self::resolveValue($value, $context);
        }

        return $resolved;
    }

    private static function resolveValue(mixed $value, ThemeRenderContext $context): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (PageLayoutFieldsComposer::isImportDynamicField($value)) {
            $normalized = PageLayoutFieldsComposer::normalizeDynamicField($value);

            return self::resolveDynamicPath($normalized['dynamic'], $context->bindings);
        }

        if (isset($value['dynamic']) && is_string($value['dynamic']) && $value['dynamic'] !== '') {
            return self::resolveDynamicPath($value['dynamic'], $context->bindings);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $bindings
     */
    private static function resolveDynamicPath(string $path, array $bindings): mixed
    {
        $segments = explode('.', $path, 2);

        if (count($segments) < 2) {
            return '';
        }

        [$source, $fieldPath] = $segments;
        $record = $bindings[$source] ?? null;

        if ($record === null) {
            return '';
        }

        return self::readFieldPath($record, $fieldPath);
    }

    private static function readFieldPath(mixed $record, string $fieldPath): mixed
    {
        $parts = explode('.', $fieldPath);
        $current = $record;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (is_array($current)) {
                $current = $current[$part] ?? null;
            } elseif (is_object($current)) {
                $current = $current->{$part} ?? null;
            } else {
                return '';
            }
        }

        if (is_scalar($current) || $current === null) {
            return $current ?? '';
        }

        if (is_array($current)) {
            return $current;
        }

        return (string) $current;
    }
}
