<?php

namespace Loom\Features;

use Loom\Features\Contracts\FormModule;

abstract class FeatureBase implements FormModule
{
    protected string $identifier;

    protected string $path;

    public function __construct(string $identifier, string $path)
    {
        $this->identifier = $identifier;
        $this->path = $path;
    }

    abstract public function featureDetails(): array;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }

    public function registerNavigation(): array
    {
        return [];
    }

    public function registerForms(): array
    {
        return [];
    }

    public function getFeatureIdentifier(): string
    {
        return $this->identifier;
    }

    public function getModulePath(): string
    {
        return $this->path;
    }

    public function getFeaturePath(): string
    {
        return $this->path;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $configPath = $this->path.'/config.php';

        if (! file_exists($configPath)) {
            return $default;
        }

        $config = require $configPath;

        return data_get($config, $key, $default);
    }

    public function getViewNamespace(): string
    {
        if (! str_contains($this->identifier, '.')) {
            return $this->identifier;
        }

        [$vendor, $name] = explode('.', $this->identifier, 2);

        return "{$vendor}-{$name}";
    }
}
