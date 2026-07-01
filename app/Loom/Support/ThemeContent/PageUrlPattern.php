<?php

namespace Loom\Support\ThemeContent;

class PageUrlPattern
{
    private const PLACEHOLDER_PATTERN = '/\{([a-z_][a-z0-9_]*)\}/';

    /**
     * @return list<string>
     */
    public static function extractPlaceholders(string $url): array
    {
        $normalized = strtolower(trim($url, '/'));

        if ($normalized === '') {
            return [];
        }

        if (! preg_match_all(self::PLACEHOLDER_PATTERN, $normalized, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    public static function isPattern(string $url): bool
    {
        return self::extractPlaceholders($url) !== [];
    }

    public static function templateKey(string $pattern): string
    {
        $normalized = strtolower(trim($pattern, '/'));

        return (string) preg_replace(self::PLACEHOLDER_PATTERN, '{}', $normalized);
    }

    public static function toRegex(string $pattern): string
    {
        $normalized = strtolower(trim($pattern, '/'));

        if ($normalized === '') {
            return '^$';
        }

        $parts = explode('/', $normalized);
        $regexParts = [];

        foreach ($parts as $part) {
            if (preg_match('/^\{([a-z_][a-z0-9_]*)\}$/', $part, $match)) {
                $regexParts[] = '(?P<'.$match[1].'>[^/]+)';

                continue;
            }

            $regexParts[] = preg_quote($part, '/');
        }

        return '^'.implode('/', $regexParts).'$';
    }

    /**
     * @return array<string, string>|null
     */
    public static function match(string $pattern, string $path): ?array
    {
        $normalizedPath = strtolower(trim($path, '/'));
        $normalizedPattern = strtolower(trim($pattern, '/'));

        if ($normalizedPattern === '' && $normalizedPath === '') {
            return [];
        }

        if ($normalizedPattern === '' || $normalizedPath === '') {
            return null;
        }

        if (! self::isPattern($pattern)) {
            return $normalizedPattern === $normalizedPath ? [] : null;
        }

        $regex = self::toRegex($pattern);

        if (! preg_match('#'.$regex.'#', $normalizedPath, $matches)) {
            return null;
        }

        $params = [];

        foreach (self::extractPlaceholders($pattern) as $name) {
            if (isset($matches[$name]) && is_string($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    public static function staticSegmentCount(string $pattern): int
    {
        $normalized = strtolower(trim($pattern, '/'));

        if ($normalized === '') {
            return 0;
        }

        $count = 0;

        foreach (explode('/', $normalized) as $part) {
            if ($part !== '' && ! preg_match('/^\{[a-z_][a-z0-9_]*\}$/', $part)) {
                $count++;
            }
        }

        return $count;
    }
}
