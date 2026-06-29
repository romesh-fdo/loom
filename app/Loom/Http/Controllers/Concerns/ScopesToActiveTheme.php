<?php

namespace Loom\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Loom\Support\ThemeManager;

trait ScopesToActiveTheme
{
    protected function activeThemeSlug(): string
    {
        return app(ThemeManager::class)->activeSlug();
    }

    protected function activeTheme(): ?array
    {
        return app(ThemeManager::class)->find($this->activeThemeSlug());
    }

    /**
     * @return Builder<Model>
     */
    protected function themedQuery(): Builder
    {
        return $this->modelClass()::query()->forTheme($this->activeThemeSlug());
    }

    protected function resolveRouteModel(Request $request): Model
    {
        $record = $request->route($this->routeModelKey());
        $id = $record instanceof Model ? $record->getKey() : $record;

        return $this->themedQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function withThemeSlug(array $validated, Request $request): array
    {
        $record = $request->route($this->routeModelKey());

        if ($record === null) {
            $validated['theme_slug'] = $this->activeThemeSlug();
        }

        return $validated;
    }
}
