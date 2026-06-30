<?php

namespace Loom\Support\FileManager;

use Alexusmai\LaravelFileManager\FileManager as BaseFileManager;

class FileManager extends BaseFileManager
{
    protected function filterDir($disk, $content): array
    {
        return $this->withoutGitIgnore(parent::filterDir($disk, $content));
    }

    protected function filterFile($disk, $content): array
    {
        return $this->withoutGitIgnore(parent::filterFile($disk, $content));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function withoutGitIgnore(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item) => strtolower($item['basename'] ?? '') !== '.gitignore',
        ));
    }
}
