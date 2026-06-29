<?php

namespace Loom\Features\Contracts;

interface FormModule
{
    public function getModulePath(): string;

    public function getViewNamespace(): string;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function registerForms(): array;

    public function getConfig(string $key, mixed $default = null): mixed;
}
