<?php

namespace Loom\Builder;

class PluginManifestGenerator
{
    public function generate(Blueprint $blueprint): string
    {
        $label = $this->escapeYaml($blueprint->pluginLabel());

        $lines = [
            'name: '.$label,
            'description: Manage '.$label.' for Loom CMS',
            'author: Loom',
            'icon: '.$blueprint->pluginIcon(),
            'version: 1.0.0',
            'route: '.$blueprint->routeSlug(),
            'model: '.$blueprint->modelClass(),
            'table: '.$blueprint->tableName(),
        ];

        return implode("\n", $lines)."\n";
    }

    protected function escapeYaml(string $value): string
    {
        if (preg_match('/[:\[\]{}#&*!|>\'"%@`]/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
