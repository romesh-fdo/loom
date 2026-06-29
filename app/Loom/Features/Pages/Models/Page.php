<?php

namespace Loom\Features\Pages\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = \Loom\Builder\TableNames::applyPrefix('pages');

        parent::__construct($attributes);
    }

    protected $fillable = [
        'name',
        'url',
        'sections',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
        ];
    }
}
