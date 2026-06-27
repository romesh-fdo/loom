<?php

namespace Loom\Blocks\Models;

use Illuminate\Database\Eloquent\Model;
use Loom\Blocks\Models\Generated\BlockSchema;

class Block extends Model
{
    use BlockSchema;

    protected function casts(): array
    {
        return static::loomCasts();
    }
}
