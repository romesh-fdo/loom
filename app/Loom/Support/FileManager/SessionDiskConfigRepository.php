<?php

namespace Loom\Support\FileManager;

use Alexusmai\LaravelFileManager\Services\ConfigService\ConfigRepository;
use Alexusmai\LaravelFileManager\Services\ConfigService\DefaultConfigRepository;

class SessionDiskConfigRepository implements ConfigRepository
{
    protected DefaultConfigRepository $default;

    public function __construct(?DefaultConfigRepository $default = null)
    {
        $this->default = $default ?? new DefaultConfigRepository;
    }

    protected function activeDisk(): string
    {
        $disk = session('loom.file_manager.disk');
        $allowed = config('file-manager.diskList', ['assets']);

        if (is_string($disk) && in_array($disk, $allowed, true)) {
            return $disk;
        }

        return $allowed[0] ?? 'assets';
    }

    public function getRoutePrefix(): string
    {
        return $this->default->getRoutePrefix();
    }

    public function getDiskList(): array
    {
        return [$this->activeDisk()];
    }

    public function getLeftDisk(): ?string
    {
        return $this->activeDisk();
    }

    public function getRightDisk(): ?string
    {
        return $this->default->getRightDisk();
    }

    public function getLeftPath(): ?string
    {
        return $this->default->getLeftPath();
    }

    public function getRightPath(): ?string
    {
        return $this->default->getRightPath();
    }

    public function getWindowsConfig(): int
    {
        return $this->default->getWindowsConfig();
    }

    public function getMaxUploadFileSize(): ?int
    {
        return $this->default->getMaxUploadFileSize();
    }

    public function getAllowFileTypes(): array
    {
        return $this->default->getAllowFileTypes();
    }

    public function getHiddenFiles(): bool
    {
        return $this->default->getHiddenFiles();
    }

    public function getMiddleware(): array
    {
        return $this->default->getMiddleware();
    }

    public function getAcl(): bool
    {
        return $this->default->getAcl();
    }

    public function getAclHideFromFM(): bool
    {
        return $this->default->getAclHideFromFM();
    }

    public function getAclStrategy(): string
    {
        return $this->default->getAclStrategy();
    }

    public function getAclRepository(): string
    {
        return $this->default->getAclRepository();
    }

    public function getAclRulesCache(): ?int
    {
        return $this->default->getAclRulesCache();
    }

    public function getSlugifyNames(): ?bool
    {
        return $this->default->getSlugifyNames();
    }
}
