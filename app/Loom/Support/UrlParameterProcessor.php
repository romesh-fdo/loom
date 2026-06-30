<?php

namespace Loom\Support;

use Closure;

class UrlParameterProcessor
{
    public const URL_TYPE = 'url';

    /**
     * @return array{url: string, class: string, id: string, target: string}
     */
    public static function normalizeCompoundValue(mixed $value): array
    {
        if (is_string($value)) {
            return [
                'url' => $value,
                'class' => '',
                'id' => '',
                'target' => '',
            ];
        }

        if (! is_array($value)) {
            return [
                'url' => '',
                'class' => '',
                'id' => '',
                'target' => '',
            ];
        }

        $target = $value['target'] ?? '';

        if ($target !== '_blank' && ! empty($value['open_in_new_tab'])) {
            $target = '_blank';
        }

        return [
            'url' => is_string($value['url'] ?? null) ? $value['url'] : '',
            'class' => is_string($value['class'] ?? null) ? $value['class'] : (is_string($value['className'] ?? null) ? $value['className'] : ''),
            'id' => is_string($value['id'] ?? null) ? $value['id'] : '',
            'target' => $target === '_blank' ? '_blank' : '',
        ];
    }

    public static function isUrlType(string $type): bool
    {
        return $type === self::URL_TYPE;
    }

    public static function validateCompoundValue(mixed $value, string $parameterName, Closure $fail): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_string($value)) {
            return true;
        }

        if (! is_array($value)) {
            $fail('Parameter "'.$parameterName.'" must be a URL object with url, class, id, and target.');

            return false;
        }

        foreach (['url', 'class', 'id', 'target'] as $key) {
            if (array_key_exists($key, $value) && $value[$key] !== null && ! is_string($value[$key])) {
                $fail('Parameter "'.$parameterName.'.'.$key.'" must be a string.');

                return false;
            }
        }

        if (isset($value['target']) && is_string($value['target']) && $value['target'] !== '' && $value['target'] !== '_blank') {
            $fail('Parameter "'.$parameterName.'.target" must be blank or "_blank".');

            return false;
        }

        $url = $value['url'] ?? '';

        if (is_string($url) && $url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
            $fail('Parameter "'.$parameterName.'.url" must be a valid URL.');

            return false;
        }

        return true;
    }
}
