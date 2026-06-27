<?php

namespace Loom\Blocks\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'code', 'config'])]
class Block extends Model
{
    protected $table = 'blocks';

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
