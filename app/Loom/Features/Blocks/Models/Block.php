<?php

namespace Loom\Features\Blocks\Models;

use Illuminate\Database\Eloquent\Model;
use Loom\Support\Concerns\BelongsToTheme;

class Block extends Model
{
    use BelongsToTheme;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = \Loom\Builder\TableNames::applyPrefix('blocks');

        parent::__construct($attributes);
    }

    protected $fillable = [
        'theme_slug',
        'name',
        'code',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'code' => 'array',
            'config' => 'array',
        ];
    }
}
