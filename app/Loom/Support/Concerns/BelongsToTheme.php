<?php

namespace Loom\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTheme
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTheme(Builder $query, string $themeSlug): Builder
    {
        return $query->where($this->getTable().'.theme_slug', $themeSlug);
    }
}
