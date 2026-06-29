<?php

namespace Loom\Features\Pages\Controllers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Loom\Features\Blocks\Models\Block;
use Loom\Features\Pages\Models\Page;
use Loom\Http\Controllers\FormResourceController;
use Loom\Support\FormSchema;

class PagesController extends FormResourceController
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 12) ?? 12);

        $pages = Page::query()
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('loom-pages::index', [
            'pages' => $pages,
            'search' => $search->toString(),
        ]);
    }

    protected function formViewData(?Model $record = null): array
    {
        $data = parent::formViewData($record);
        $catalog = $this->blocksCatalog();

        if (isset($data['forms']['basic-form']['fields']['sections'])) {
            $data['forms']['basic-form']['fields']['sections']['blocksCatalog'] = $catalog;
        }

        $data['blocksCatalog'] = $catalog;

        return $data;
    }

    protected function validateRecord(Request $request): array
    {
        $formDefinitions = $this->sortedFormDefinitions();
        $rules = FormSchema::validationRulesForDefinitions($this->pluginId(), $formDefinitions);

        $pageId = $request->route('page');
        $pageId = $pageId instanceof Page ? $pageId->getKey() : $pageId;

        $rules['url'][] = Rule::unique(Page::query()->getModel()->getTable(), 'url')->ignore($pageId);
        $rules['sections'] = ['nullable', 'array', $this->sectionsStructureRule()];

        $validated = $request->validate($rules);

        if (isset($validated['url']) && is_string($validated['url'])) {
            $validated['url'] = strtolower(trim($validated['url'], '/'));
        }

        $validated['sections'] = $this->normalizeSections($validated['sections'] ?? []);

        return FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function blocksCatalog(): array
    {
        return Block::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Block $block) => [
                'id' => $block->id,
                'name' => $block->name,
                'parameters' => $block->code['parameters'] ?? [],
            ])
            ->values()
            ->all();
    }

    protected function sectionsStructureRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value)) {
                $fail('Sections must be an array.');

                return;
            }

            $blocksById = Block::query()->get()->keyBy('id');

            foreach ($value as $index => $section) {
                if (! is_array($section)) {
                    $fail('Section at index '.$index.' must be an object.');

                    return;
                }

                $blockId = $section['block_id'] ?? null;

                if ($blockId === null || $blockId === '') {
                    $fail('Section at index '.$index.' must include a block.');

                    return;
                }

                $block = $blocksById->get((int) $blockId);

                if ($block === null) {
                    $fail('Section at index '.$index.' references an unknown block.');

                    return;
                }

                $values = $section['values'] ?? [];

                if (! is_array($values)) {
                    $fail('Section at index '.$index.' values must be an object.');

                    return;
                }

                $parameters = $block->code['parameters'] ?? [];
                $allowedNames = collect($parameters)->pluck('name')->all();

                foreach ($values as $key => $paramValue) {
                    if (! in_array($key, $allowedNames, true)) {
                        $fail('Section at index '.$index.' has an unknown parameter "'.$key.'".');

                        return;
                    }
                }

                foreach ($parameters as $parameter) {
                    if (! is_array($parameter)) {
                        continue;
                    }

                    $name = $parameter['name'] ?? null;
                    $type = $parameter['type'] ?? 'text';

                    if (! is_string($name) || $name === '') {
                        continue;
                    }

                    $paramValue = $values[$name] ?? null;

                    if ($type === 'checkbox') {
                        continue;
                    }

                    if ($paramValue === null || $paramValue === '') {
                        continue;
                    }

                    if ($type === 'number' && ! is_numeric($paramValue)) {
                        $fail('Parameter "'.$name.'" in section '.$index.' must be a number.');

                        return;
                    }

                    if ($type === 'email' && ! filter_var((string) $paramValue, FILTER_VALIDATE_EMAIL)) {
                        $fail('Parameter "'.$name.'" in section '.$index.' must be a valid email.');

                        return;
                    }
                }
            }
        };
    }

    /**
     * @param  array<int, mixed>  $sections
     * @return list<array{block_id: int, values: array<string, mixed>}>
     */
    protected function normalizeSections(array $sections): array
    {
        return collect($sections)
            ->filter(fn ($section) => is_array($section) && ! empty($section['block_id']))
            ->map(function (array $section) {
                $values = $section['values'] ?? [];

                return [
                    'block_id' => (int) $section['block_id'],
                    'values' => is_array($values) ? $values : [],
                ];
            })
            ->values()
            ->all();
    }

    protected function pluginId(): string
    {
        return 'loom.pages';
    }

    protected function modelClass(): string
    {
        return Page::class;
    }

    protected function viewNamespace(): string
    {
        return 'loom-pages';
    }

    protected function routeModelKey(): string
    {
        return 'page';
    }

    protected function indexRoute(): string
    {
        return 'loom.pages.index';
    }

    protected function resourceLabel(): string
    {
        return 'Page';
    }
}
