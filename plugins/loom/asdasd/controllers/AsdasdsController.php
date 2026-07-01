<?php

namespace Loom\Asdasd\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Asdasd\Models\Asdasd;
use Loom\Http\Controllers\FormResourceController;

class AsdasdsController extends FormResourceController
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->plugin()->getConfig('per_page', 12) ?? 12);
        $searchField = $this->plugin()->getConfig('search_field', 'name');

        $records = Asdasd::query()
            ->when($search->isNotEmpty(), fn ($query) => $query->where($searchField, 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('loom-asdasd::index', [
            'asdasds' => $records,
            'search' => $search->toString(),
        ]);
    }

    protected function pluginId(): string
    {
        return 'loom.asdasd';
    }

    protected function modelClass(): string
    {
        return Asdasd::class;
    }

    protected function viewNamespace(): string
    {
        return 'loom-asdasd';
    }

    protected function routeModelKey(): string
    {
        return 'asdasd';
    }

    protected function indexRoute(): string
    {
        return 'loom.asdasd.index';
    }

    protected function resourceLabel(): string
    {
        return 'asdasd';
    }
}
