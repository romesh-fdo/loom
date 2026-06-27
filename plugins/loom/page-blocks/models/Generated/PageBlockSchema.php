<?php

namespace Loom\PageBlocks\Models\Generated;

trait PageBlockSchema
{
    public function getTable(): string
    {
        return 'loom_page_blocks';
    }

    public function initializePageBlockSchema(): void
    {
        $this->fillable = static::loomFillable();
    }

    /**
     * @return list<string>
     */
    public static function loomFillable(): array
    {
        return [
            0 => 'block_name',
            1 => 'block_html',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function loomCasts(): array
    {
        return [
            'block_html' => 'array',
        ];
    }
}
