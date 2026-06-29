<?php

namespace Loom\Support;

use RuntimeException;

class EnvWriter
{
    public function set(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            throw new RuntimeException('.env file not found.');
        }

        $content = file_get_contents($path);
        $escaped = $this->escapeValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$escaped}", $content);
        } else {
            $content = rtrim($content).PHP_EOL."{$key}={$escaped}".PHP_EOL;
        }

        file_put_contents($path, $content);
    }

    protected function escapeValue(string $value): string
    {
        if (preg_match('/\s|#/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
