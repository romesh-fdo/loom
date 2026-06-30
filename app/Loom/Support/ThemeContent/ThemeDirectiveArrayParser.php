<?php

namespace Loom\Support\ThemeContent;

class ThemeDirectiveArrayParser
{
    /**
     * @return array<int|string, mixed>|null
     */
    public static function parse(string $literal): ?array
    {
        $literal = trim($literal);

        if ($literal === '' || $literal[0] !== '[') {
            return null;
        }

        try {
            [$value] = self::parseValue($literal, 0);

            return is_array($value) ? $value : null;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array{0: mixed, 1: int}
     */
    private static function parseValue(string $input, int $pos): array
    {
        $pos = self::skipWhitespace($input, $pos);
        $length = strlen($input);

        if ($pos >= $length) {
            throw new \InvalidArgumentException('Unexpected end of input.');
        }

        $char = $input[$pos];

        if ($char === '[') {
            return self::parseArray($input, $pos);
        }

        if ($char === "'" || $char === '"') {
            return self::parseString($input, $pos, $char);
        }

        if (ctype_digit($char) || ($char === '-' && isset($input[$pos + 1]) && ctype_digit($input[$pos + 1]))) {
            return self::parseNumber($input, $pos);
        }

        if (substr($input, $pos, 4) === 'true') {
            return [true, $pos + 4];
        }

        if (substr($input, $pos, 5) === 'false') {
            return [false, $pos + 5];
        }

        if (substr($input, $pos, 4) === 'null') {
            return [null, $pos + 4];
        }

        throw new \InvalidArgumentException('Unexpected token at position '.$pos.'.');
    }

    /**
     * @return array{0: array<int|string, mixed>, 1: int}
     */
    private static function parseArray(string $input, int $pos): array
    {
        $pos++;
        $result = [];
        $pos = self::skipWhitespace($input, $pos);
        $length = strlen($input);

        if ($pos < $length && $input[$pos] === ']') {
            return [$result, $pos + 1];
        }

        while ($pos < $length) {
            $pos = self::skipWhitespace($input, $pos);

            if ($pos < $length && $input[$pos] === ']') {
                return [$result, $pos + 1];
            }

            $key = null;
            $peek = $input[$pos] ?? '';

            if ($peek === "'" || $peek === '"') {
                [$key, $pos] = self::parseString($input, $pos, $peek);
                $pos = self::skipWhitespace($input, $pos);

                if ($pos + 1 < $length && $input[$pos] === '=' && $input[$pos + 1] === '>') {
                    $pos += 2;
                    $pos = self::skipWhitespace($input, $pos);
                    [$value, $pos] = self::parseValue($input, $pos);
                    $result[$key] = $value;
                } else {
                    $result[] = $key;
                }
            } else {
                [$value, $pos] = self::parseValue($input, $pos);
                $result[] = $value;
            }

            $pos = self::skipWhitespace($input, $pos);

            if ($pos < $length && $input[$pos] === ',') {
                $pos++;

                continue;
            }

            if ($pos < $length && $input[$pos] === ']') {
                return [$result, $pos + 1];
            }

            throw new \InvalidArgumentException('Expected comma or closing bracket.');
        }

        throw new \InvalidArgumentException('Unclosed array.');
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function parseString(string $input, int $pos, string $quote): array
    {
        $pos++;
        $length = strlen($input);
        $value = '';

        while ($pos < $length) {
            $char = $input[$pos];

            if ($char === '\\' && $pos + 1 < $length) {
                $value .= $input[$pos + 1];
                $pos += 2;

                continue;
            }

            if ($char === $quote) {
                return [$value, $pos + 1];
            }

            $value .= $char;
            $pos++;
        }

        throw new \InvalidArgumentException('Unclosed string.');
    }

    /**
     * @return array{0: int|float, 1: int}
     */
    private static function parseNumber(string $input, int $pos): array
    {
        $start = $pos;

        if ($input[$pos] === '-') {
            $pos++;
        }

        while ($pos < strlen($input) && ctype_digit($input[$pos])) {
            $pos++;
        }

        if ($pos < strlen($input) && $input[$pos] === '.') {
            $pos++;

            while ($pos < strlen($input) && ctype_digit($input[$pos])) {
                $pos++;
            }

            return [(float) substr($input, $start, $pos - $start), $pos];
        }

        return [(int) substr($input, $start, $pos - $start), $pos];
    }

    private static function skipWhitespace(string $input, int $pos): int
    {
        $length = strlen($input);

        while ($pos < $length && ctype_space($input[$pos])) {
            $pos++;
        }

        return $pos;
    }
}
