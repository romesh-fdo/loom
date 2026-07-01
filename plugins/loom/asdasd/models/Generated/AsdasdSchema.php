<?php

namespace Loom\Asdasd\Models\Generated;

trait AsdasdSchema
{
    public function getTable(): string
    {
        return 'loom_asdasds';
    }

    public function initializeAsdasdSchema(): void
    {
        $this->fillable = static::loomFillable();
    }

    /**
     * @return list<string>
     */
    public static function loomFillable(): array
    {
        return [
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function loomCasts(): array
    {
        return [];
    }
}
