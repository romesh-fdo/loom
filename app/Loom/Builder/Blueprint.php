<?php

namespace Loom\Builder;

use Illuminate\Support\Str;

class Blueprint
{
    public function __construct(
        protected array $definition = [],
        public readonly ?string $pluginIdentifier = null,
        public readonly string $status = 'draft',
    ) {}

    public static function fromArray(array $definition, ?string $pluginIdentifier = null, string $status = 'draft'): self
    {
        return new self($definition, $pluginIdentifier, $status);
    }

    public function toArray(): array
    {
        return $this->definition;
    }

    public function isNewPlugin(): bool
    {
        return (bool) ($this->definition['is_new'] ?? false);
    }

    public function pluginSlug(): string
    {
        return (string) ($this->definition['plugin']['name'] ?? '');
    }

    public function pluginLabel(): string
    {
        return (string) ($this->definition['plugin']['label'] ?? Str::headline($this->pluginSlug()));
    }

    public function routeSlug(): string
    {
        return (string) ($this->definition['plugin']['route'] ?? Str::plural($this->pluginSlug()));
    }

    public function pluginIcon(): string
    {
        return (string) ($this->definition['plugin']['icon'] ?? 'bi-box');
    }

    public function modelClass(): string
    {
        return (string) ($this->definition['model']['class'] ?? Str::studly(Str::singular($this->pluginSlug())));
    }

    public function tableName(): string
    {
        if (! empty($this->definition['model']['table'])) {
            return (string) $this->definition['model']['table'];
        }

        return TableNames::defaultForSlug($this->pluginSlug());
    }

    public function namespaceStudly(): string
    {
        return Str::studly($this->pluginSlug());
    }

    public function viewNamespace(): string
    {
        return 'loom-'.$this->pluginSlug();
    }

    public function identifier(): string
    {
        return $this->pluginIdentifier ?? 'loom.'.$this->pluginSlug();
    }

    public function pluginPath(): string
    {
        return config('loom.plugins_path', base_path('plugins')).'/loom/'.$this->pluginSlug();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forms(): array
    {
        return $this->definition['forms'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modelFields(): array
    {
        $fields = [];

        foreach ($this->forms() as $form) {
            foreach ($form['fields'] ?? [] as $field) {
                if (($field['storage'] ?? $form['storage'] ?? 'model') === 'model') {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function configFields(): array
    {
        $fields = [];

        foreach ($this->forms() as $form) {
            foreach ($form['fields'] ?? [] as $field) {
                if (($field['storage'] ?? $form['storage'] ?? 'model') === 'config') {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    public function hasConfigFields(): bool
    {
        return $this->configFields() !== [];
    }

    public function withDefinition(array $definition): self
    {
        return new self($definition, $this->pluginIdentifier, $this->status);
    }
}
