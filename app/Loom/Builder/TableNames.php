<?php

namespace Loom\Builder;

use Illuminate\Support\Str;

class TableNames
{
    public static function prefix(): string
    {
        return (string) config('loom.table_prefix', 'loom_');
    }

    public static function applyPrefix(string $table): string
    {
        $table = strtolower(trim($table));

        if ($table === '') {
            return $table;
        }

        $prefix = self::prefix();

        if ($prefix === '' || str_starts_with($table, $prefix)) {
            return $table;
        }

        return $prefix.$table;
    }

    public static function stripPrefix(string $table): string
    {
        $prefix = self::prefix();

        if ($prefix !== '' && str_starts_with($table, $prefix)) {
            return substr($table, strlen($prefix));
        }

        return $table;
    }

    public static function defaultForSlug(string $pluginSlug): string
    {
        $base = Str::snake(Str::plural(str_replace('-', '_', $pluginSlug)));

        return self::applyPrefix($base);
    }
}
