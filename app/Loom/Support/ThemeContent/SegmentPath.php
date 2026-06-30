<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Support\Str;
use InvalidArgumentException;

class SegmentPath
{
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', trim($path, '/'));
        $path = preg_replace('#/+#', '/', $path) ?? '';

        return $path;
    }

    public static function validate(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException('Invalid path.');
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Invalid path segment.');
            }

            if (! preg_match('/^[a-z0-9][a-z0-9\-]*$/', $segment)) {
                throw new InvalidArgumentException("Invalid path segment [{$segment}].");
            }
        }
    }

    public static function join(?string $folder, string $filename): string
    {
        $folder = self::normalize($folder ?? '');
        $filename = self::normalize($filename);

        if ($filename === '') {
            throw new InvalidArgumentException('Filename is required.');
        }

        if (str_contains($filename, '/')) {
            throw new InvalidArgumentException('Filename cannot contain slashes.');
        }

        self::validate($filename);

        if ($folder === '') {
            return $filename;
        }

        self::validate($folder);

        return $folder.'/'.$filename;
    }

    public static function dirname(string $path): string
    {
        $path = self::normalize($path);

        if ($path === '' || ! str_contains($path, '/')) {
            return '';
        }

        return self::normalize(dirname($path));
    }

    public static function basename(string $path): string
    {
        $path = self::normalize($path);

        if ($path === '') {
            return '';
        }

        return basename($path);
    }

    public static function slugifySegment(string $name): string
    {
        $slug = Str::slug(trim($name));

        if ($slug === '') {
            $slug = 'segment';
        }

        self::validate($slug);

        return $slug;
    }
}
