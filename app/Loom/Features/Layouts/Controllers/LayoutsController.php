<?php

namespace Loom\Features\Layouts\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Http\Controllers\ThemeFileResourceController;
use Loom\Support\ThemeContent\LayoutStore;
use Loom\Support\ThemeContent\ThemeFileStore;

class LayoutsController extends ThemeFileResourceController
{
    public function __construct(
        protected LayoutStore $layouts,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 12) ?? 12);

        $layouts = $this->layouts->paginate(
            $search->isNotEmpty() ? $search->toString() : null,
            $perPage,
            max(1, (int) $request->input('page', 1)),
            $this->activeThemeSlug()
        );

        return view('loom-layouts::index', [
            'layouts' => $layouts,
            'search' => $search->toString(),
        ]);
    }

    protected function pluginId(): string
    {
        return 'loom.layouts';
    }

    protected function fileStore(): ThemeFileStore
    {
        return $this->layouts;
    }

    protected function viewNamespace(): string
    {
        return 'loom-layouts';
    }

    protected function routeRecordKey(): string
    {
        return 'layoutSlug';
    }

    protected function indexRoute(): string
    {
        return 'loom.layouts.index';
    }

    protected function resourceLabel(): string
    {
        return 'Layout';
    }
}
