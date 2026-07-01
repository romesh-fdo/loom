<?php

namespace Loom\Asdasd\Models;

use Illuminate\Database\Eloquent\Model;
use Loom\Asdasd\Models\Generated\AsdasdSchema;

class Asdasd extends Model
{
    use AsdasdSchema;

    protected function casts(): array
    {
        return static::loomCasts();
    }
}
