<?php

namespace Loom\PageBlocks\Models;

use Illuminate\Database\Eloquent\Model;
use Loom\PageBlocks\Models\Generated\PageBlockSchema;

class PageBlock extends Model
{
    use PageBlockSchema;

    protected function casts(): array
    {
        return static::loomCasts();
    }
}
