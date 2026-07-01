<?php

namespace Loom\Support\ThemeContent;

use Illuminate\Http\Request;
use Loom\System\PluginManager;

class PageEntityResolver
{
    public function __construct(
        protected PluginManager $plugins,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $entityImports
     * @return array<string, mixed>
     */
    public function resolve(array $entityImports, Request $request): array
    {
        $bindings = [];

        foreach ($entityImports as $import) {
            if (! is_array($import)) {
                continue;
            }

            $variable = trim((string) ($import['variable'] ?? ''));
            $plugin = trim((string) ($import['plugin'] ?? ''));
            $function = trim((string) ($import['function'] ?? ''));

            if ($variable === '' || $plugin === '' || $function === '') {
                continue;
            }

            $parameters = is_array($import['parameters'] ?? null) ? $import['parameters'] : [];
            $arguments = $this->resolveArguments($parameters, $request);
            $result = $this->plugins->callFunction($plugin, $function, $arguments);

            if ($result !== null) {
                $bindings[$variable] = $result;
            }
        }

        return $bindings;
    }

    /**
     * @param  array<string, array<string, mixed>>  $parameters
     * @return array<string, mixed>
     */
    protected function resolveArguments(array $parameters, Request $request): array
    {
        $arguments = [];

        foreach ($parameters as $name => $binding) {
            if (! is_string($name) || $name === '' || ! is_array($binding)) {
                continue;
            }

            $arguments[$name] = $this->resolveParameterBinding($binding, $request);
        }

        return $arguments;
    }

    /**
     * @param  array<string, mixed>  $binding
     */
    protected function resolveParameterBinding(array $binding, Request $request): mixed
    {
        $mode = (string) ($binding['mode'] ?? 'static');

        return match ($mode) {
            'path_param' => (string) ($request->route((string) ($binding['param'] ?? '')) ?? ''),
            'query_param' => (string) ($request->query((string) ($binding['param'] ?? '')) ?? ''),
            'url_segment' => (string) ($request->segment(max(1, (int) ($binding['segment'] ?? 1))) ?? ''),
            'route_param' => (string) ($request->route((string) ($binding['param'] ?? '')) ?? ''),
            default => (string) ($binding['value'] ?? ''),
        };
    }
}
