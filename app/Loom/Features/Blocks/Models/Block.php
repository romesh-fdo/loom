<?php

namespace Loom\Features\Blocks\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = \Loom\Builder\TableNames::applyPrefix('blocks');

        parent::__construct($attributes);
    }
    protected $fillable = [
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
