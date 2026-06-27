<?php

namespace Loom\Builder;

use InvalidArgumentException;
use RuntimeException;

class SecureFileWriter
{
    public function __construct(
        protected string $pluginsPath,
    ) {
        $this->pluginsPath = rtrim($pluginsPath, DIRECTORY_SEPARATOR);
    }

    public static function make(): self
    {
        return new self(config('loom.plugins_path', base_path('plugins')));
    }

    public function pluginRoot(string $pluginSlug): string
    {
        if (! preg_match('/^[a-z][a-z0-9-]*$/', $pluginSlug)) {
            throw new InvalidArgumentException('Invalid plugin slug.');
        }

        return $this->pluginsPath.'/loom/'.$pluginSlug;
    }

    public function resolvePath(string $pluginSlug, string $relativePath): string
    {
        $root = $this->pluginRoot($pluginSlug);
        $full = $root.'/'.ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');
        $normalizedRoot = str_replace('\\', '/', $root);
        $normalizedFull = str_replace('\\', '/', $full);

        if ($normalizedFull !== $normalizedRoot && ! str_starts_with($normalizedFull, $normalizedRoot.'/')) {
            throw new InvalidArgumentException('Path escapes plugin directory.');
        }

        $extension = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if (! in_array($extension, ['php', 'json', 'yaml', 'blade.php'], true) && ! str_ends_with($relativePath, '.blade.php')) {
            if ($extension !== '' && $extension !== 'php') {
                throw new InvalidArgumentException('File extension not allowed.');
            }
        }

        return $full;
    }

    public function write(string $pluginSlug, string $relativePath, string $contents, bool $allowOverwrite = true): string
    {
        $path = $this->resolvePath($pluginSlug, $relativePath);

        if (file_exists($path) && ! $allowOverwrite) {
            throw new RuntimeException("Refusing to overwrite existing file: {$relativePath}");
        }

        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create directory: {$directory}");
        }

        $temp = $path.'.'.uniqid('loom_', true).'.tmp';

        if (file_put_contents($temp, $contents) === false) {
            throw new RuntimeException("Unable to write temp file: {$temp}");
        }

        if (file_exists($path) && ! unlink($path)) {
            @unlink($temp);
            throw new RuntimeException("Unable to replace file: {$path}");
        }

        if (! rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException("Unable to move temp file into place: {$path}");
        }

        return $path;
    }

    public function exists(string $pluginSlug, string $relativePath): bool
    {
        return file_exists($this->resolvePath($pluginSlug, $relativePath));
    }

    public function read(string $pluginSlug, string $relativePath): ?string
    {
        $path = $this->resolvePath($pluginSlug, $relativePath);

        return file_exists($path) ? file_get_contents($path) : null;
    }
}
