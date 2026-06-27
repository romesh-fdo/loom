<?php

namespace Loom\Blocks\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Blocks\Models\Block;
use Loom\Http\Controllers\FormResourceController;

class BlocksController extends FormResourceController
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->plugin()->getConfig('per_page', 12) ?? 12);

        $blocks = Block::query()
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('loom-blocks::index', [
            'blocks' => $blocks,
            'search' => $search->toString(),
        ]);
    }

    protected function pluginId(): string
    {
        return 'loom.blocks';
    }

    protected function modelClass(): string
    {
        return Block::class;
    }

    protected function viewNamespace(): string
    {
        return 'loom-blocks';
    }

    protected function routeModelKey(): string
    {
        return 'block';
    }

    protected function indexRoute(): string
    {
        return 'loom.blocks.index';
    }

    protected function resourceLabel(): string
    {
        return 'Block';
    }
}
