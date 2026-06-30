<?php

namespace Loom\Http\Controllers\Concerns;

use Closure;
use Loom\Support\DynamicParameterTypes;
use Loom\Support\ParameterLayout;

trait ValidatesDynamicCode
{
    protected function dynamicCodeStructureRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $decoded = json_decode((string) $value, true);

            if (! is_array($decoded)) {
                $fail('The code field must be a valid JSON object.');

                return;
            }

            if (! isset($decoded['template']) || ! is_string($decoded['template'])) {
                $fail('The code template is required.');

                return;
            }

            if (trim($decoded['template']) === '') {
                $fail('The code template cannot be empty.');

                return;
            }

            if (! isset($decoded['parameters']) || ! is_array($decoded['parameters'])) {
                $fail('The code parameters must be an array.');

                return;
            }

            $names = [];

            foreach ($decoded['parameters'] as $index => $parameter) {
                if (! is_array($parameter)) {
                    $fail('Parameter at index '.$index.' must be an object.');

                    return;
                }

                foreach (['name', 'label', 'type'] as $key) {
                    if (! isset($parameter[$key]) || ! is_string($parameter[$key]) || $parameter[$key] === '') {
                        $fail('Parameter at index '.$index.' is missing a valid '.$key.'.');

                        return;
                    }
                }

                if (! preg_match('/^[a-z][a-z0-9_]*$/', $parameter['name'])) {
                    $fail('Parameter name "'.$parameter['name'].'" is invalid.');

                    return;
                }

                if (in_array($parameter['name'], $names, true)) {
                    $fail('Parameter name "'.$parameter['name'].'" is duplicated.');

                    return;
                }

                $names[] = $parameter['name'];

                if (! in_array($parameter['type'], DynamicParameterTypes::allowedTypes(), true)) {
                    $fail('Parameter type "'.$parameter['type'].'" is not allowed.');

                    return;
                }

                if (array_key_exists('tip', $parameter)) {
                    if (! is_string($parameter['tip'])) {
                        $fail('Parameter tip at index '.$index.' must be a string.');

                        return;
                    }

                    if (strlen($parameter['tip']) > 500) {
                        $fail('Parameter tip at index '.$index.' must not exceed 500 characters.');

                        return;
                    }
                }

                if (! $this->validateParameterLayout($parameter, $index, $fail)) {
                    return;
                }

                if ($parameter['type'] === 'repeater') {
                    if (! isset($parameter['item']) || ! is_string($parameter['item']) || $parameter['item'] === '') {
                        $fail('Repeater parameter at index '.$index.' must include an item variable.');

                        return;
                    }

                    if (! preg_match('/^[a-z][a-z0-9_]*$/', $parameter['item'])) {
                        $fail('Repeater item variable "'.$parameter['item'].'" is invalid.');

                        return;
                    }

                    if (! isset($parameter['fields']) || ! is_array($parameter['fields'])) {
                        $fail('Repeater parameter at index '.$index.' must include a fields array.');

                        return;
                    }

                    $fieldNames = [];

                    foreach ($parameter['fields'] as $fieldIndex => $field) {
                        if (! $this->validateScalarParameter($field, $index.'.fields.'.$fieldIndex, $fail)) {
                            return;
                        }

                        $fieldName = $field['name'];

                        if (in_array($fieldName, $fieldNames, true)) {
                            $fail('Repeater "'.$parameter['name'].'" has a duplicated field name "'.$fieldName.'".');

                            return;
                        }

                        $fieldNames[] = $fieldName;
                    }

                    continue;
                }

                if (! in_array($parameter['type'], DynamicParameterTypes::scalarTypes(), true)) {
                    $fail('Parameter type "'.$parameter['type'].'" is not allowed.');

                    return;
                }

                if (! $this->validateParameterOptions($parameter, $index, $fail)) {
                    return;
                }
            }
        };
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    private function validateScalarParameter(array $parameter, string|int $index, Closure $fail): bool
    {
        if (! is_array($parameter)) {
            $fail('Parameter at index '.$index.' must be an object.');

            return false;
        }

        foreach (['name', 'label', 'type'] as $key) {
            if (! isset($parameter[$key]) || ! is_string($parameter[$key]) || $parameter[$key] === '') {
                $fail('Parameter at index '.$index.' is missing a valid '.$key.'.');

                return false;
            }
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $parameter['name'])) {
            $fail('Parameter name "'.$parameter['name'].'" is invalid.');

            return false;
        }

        if (! in_array($parameter['type'], DynamicParameterTypes::scalarTypes(), true)) {
            $fail('Parameter type "'.$parameter['type'].'" is not allowed.');

            return false;
        }

        if (array_key_exists('tip', $parameter)) {
            if (! is_string($parameter['tip'])) {
                $fail('Parameter tip at index '.$index.' must be a string.');

                return false;
            }

            if (strlen($parameter['tip']) > 500) {
                $fail('Parameter tip at index '.$index.' must not exceed 500 characters.');

                return false;
            }
        }

        if (! $this->validateParameterLayout($parameter, $index, $fail)) {
            return false;
        }

        if (! $this->validateParameterOptions($parameter, $index, $fail)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    private function validateParameterLayout(array $parameter, string|int $index, Closure $fail): bool
    {
        if (array_key_exists('row', $parameter)) {
            if (! is_int($parameter['row']) || $parameter['row'] < 1 || $parameter['row'] > 12) {
                $fail('Parameter row at index '.$index.' must be an integer between 1 and 12.');

                return false;
            }
        }

        if (array_key_exists('colClass', $parameter)) {
            if (! is_string($parameter['colClass']) || ! ParameterLayout::isValidColClass($parameter['colClass'])) {
                $fail('Parameter colClass at index '.$index.' is not allowed.');

                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    private function validateParameterOptions(array $parameter, string|int $index, Closure $fail): bool
    {
        if (! in_array($parameter['type'], DynamicParameterTypes::optionTypes(), true)) {
            if (array_key_exists('options', $parameter)) {
                $fail('Parameter at index '.$index.' must not include options for type "'.$parameter['type'].'".');

                return false;
            }

            return true;
        }

        if (! isset($parameter['options']) || ! is_array($parameter['options']) || $parameter['options'] === []) {
            $fail('Parameter at index '.$index.' must include at least one option.');

            return false;
        }

        $values = [];

        foreach ($parameter['options'] as $optionIndex => $option) {
            if (! is_array($option)) {
                $fail('Option at index '.$index.'.'.$optionIndex.' must be an object.');

                return false;
            }

            foreach (['value', 'label'] as $key) {
                if (! isset($option[$key]) || ! is_string($option[$key]) || $option[$key] === '') {
                    $fail('Option at index '.$index.'.'.$optionIndex.' is missing a valid '.$key.'.');

                    return false;
                }
            }

            if (in_array($option['value'], $values, true)) {
                $fail('Parameter at index '.$index.' has duplicated option value "'.$option['value'].'".');

                return false;
            }

            $values[] = $option['value'];
        }

        return true;
    }
}
