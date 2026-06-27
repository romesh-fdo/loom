<?php

namespace Loom\System;

abstract class PluginBase
{
    protected string $vendor;

    protected string $name;

    protected string $path;

    public function __construct(string $vendor, string $name, string $path)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->path = $path;
    }

    abstract public function pluginDetails(): array;

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

    public function registerPermissions(): array
    {
        return [];
    }

    public function registerComponents(): array
    {
        return [];
    }

    public function registerFormWidgets(): array
    {
        return [];
    }

    public function getPluginPath(): string
    {
        return $this->path;
    }

    public function getPluginIdentifier(): string
    {
        return $this->vendor.'.'.$this->name;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getName(): string
    {
        return $this->name;
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
        return $this->vendor.'-'.$this->name;
    }
}
