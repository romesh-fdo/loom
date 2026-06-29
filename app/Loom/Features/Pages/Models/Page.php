<?php

namespace Loom\Features\Pages\Models;

use Illuminate\Database\Eloquent\Model;
use Loom\Support\Concerns\BelongsToTheme;

class Page extends Model
{
    use BelongsToTheme;

    protected $table;

    public function __construct(array $attributes = [])
    {
        $this->table = \Loom\Builder\TableNames::applyPrefix('pages');

        parent::__construct($attributes);
    }

    protected $fillable = [
        'theme_slug',
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
