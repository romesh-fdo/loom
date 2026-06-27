<?php

namespace Loom\PageBlocks\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Http\Controllers\FormResourceController;
use Loom\PageBlocks\Models\PageBlock;

class PageBlocksController extends FormResourceController
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->plugin()->getConfig('per_page', 12) ?? 12);
        $searchField = $this->plugin()->getConfig('search_field', 'name');

        $records = PageBlock::query()
            ->when($search->isNotEmpty(), fn ($query) => $query->where($searchField, 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('loom-page-blocks::index', [
            'page_blocks' => $records,
            'search' => $search->toString(),
        ]);
    }

    protected function pluginId(): string
    {
        return 'loom.page-blocks';
    }

    protected function modelClass(): string
    {
        return PageBlock::class;
    }

    protected function viewNamespace(): string
    {
        return 'loom-page-blocks';
    }

    protected function routeModelKey(): string
    {
        return 'page_block';
    }

    protected function indexRoute(): string
    {
        return 'loom.page-blocks.index';
    }

    protected function resourceLabel(): string
    {
        return 'Page Block';
    }
}
