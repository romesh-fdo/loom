<?php

namespace Loom\Support\ThemeContent;

class ThemeCodeTemplate
{
    public static function template(mixed $code): string
    {
        if (is_string($code)) {
            return $code;
        }

        if (is_array($code)) {
            return (string) ($code['template'] ?? '');
        }

        return '';
    }
}
