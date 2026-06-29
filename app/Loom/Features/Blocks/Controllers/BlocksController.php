<?php

namespace Loom\Features\Blocks\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Http\Controllers\Concerns\ValidatesDynamicCode;
use Loom\Http\Controllers\ThemeFileResourceController;
use Loom\Support\ThemeContent\BlockStore;
use Loom\Support\ThemeContent\ThemeFileStore;
use Loom\Support\FormSchema;

class BlocksController extends ThemeFileResourceController
{
    use ValidatesDynamicCode;

    public function __construct(
        protected BlockStore $blocks,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 12) ?? 12);

        $blocks = $this->blocks->paginate(
            $search->isNotEmpty() ? $search->toString() : null,
            $perPage,
            max(1, (int) $request->input('page', 1)),
            $this->activeThemeSlug()
        );

        return view('loom-blocks::index', [
            'blocks' => $blocks,
            'search' => $search->toString(),
        ]);
    }

    protected function validateRecord(Request $request): array
    {
        $formDefinitions = $this->sortedFormDefinitions();
        $rules = FormSchema::validationRulesForDefinitions($this->pluginId(), $formDefinitions);

        $rules['code'] = [
            'required',
            'json',
            $this->dynamicCodeStructureRule(),
        ];

        $validated = $request->validate($rules);

        if (isset($validated['code']) && is_string($validated['code'])) {
            $validated['code'] = json_decode($validated['code'], true);
        }

        return FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
    }

    protected function pluginId(): string
    {
        return 'loom.blocks';
    }

    protected function fileStore(): ThemeFileStore
    {
        return $this->blocks;
    }

    protected function viewNamespace(): string
    {
        return 'loom-blocks';
    }

    protected function routeRecordKey(): string
    {
        return 'blockSlug';
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
