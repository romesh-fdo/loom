<?php

namespace Loom\Features\Pages\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Http\Controllers\ThemeFileResourceController;
use Loom\Support\FormSchema;
use Loom\Support\MediaParameterProcessor;
use Loom\Support\ThemeContent\BlockStore;
use Loom\Support\ThemeContent\LayoutStore;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemeFileStore;
use Loom\Support\UrlParameterProcessor;

class PagesController extends ThemeFileResourceController
{
    public function __construct(
        protected PageStore $pages,
        protected BlockStore $blocks,
        protected LayoutStore $layouts,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 12) ?? 12);

        $pages = $this->pages->paginate(
            $search->isNotEmpty() ? $search->toString() : null,
            $perPage,
            max(1, (int) $request->input('page', 1)),
            $this->activeThemeSlug()
        );

        $layoutNames = $this->layouts->all($this->activeThemeSlug())
            ->mapWithKeys(fn (ThemeFileRecord $layout) => [$layout->slug => $layout->name])
            ->all();

        return view('loom-pages::index', [
            'pages' => $pages,
            'search' => $search->toString(),
            'layoutNames' => $layoutNames,
        ]);
    }

    protected function formViewData(?ThemeFileRecord $record = null): array
    {
        $data = parent::formViewData($record);
        $catalog = $this->blocksCatalog();
        $layouts = $this->layoutsCatalog();
        $layoutOptions = collect($layouts)
            ->map(fn (array $layout) => [
                'value' => $layout['slug'],
                'label' => $layout['name'],
            ])
            ->all();

        if (isset($data['forms']['basic-form']['fields']['sections'])) {
            $data['forms']['basic-form']['fields']['sections']['blocksCatalog'] = $catalog;
        }

        if (isset($data['forms']['basic-form']['fields']['layout'])) {
            $data['forms']['basic-form']['fields']['layout']['options'] = $layoutOptions;

            $currentValue = $data['forms']['basic-form']['fields']['layout']['value'] ?? '';

            if ($record === null && ($currentValue === '' || $currentValue === null) && $layoutOptions !== []) {
                $data['forms']['basic-form']['fields']['layout']['value'] = $layoutOptions[0]['value'];
            }
        }

        if ($record !== null && isset($data['forms']['basic-form']['fields']['url'])) {
            $url = $record->url ?? '';

            if (is_string($url) && $url === '') {
                $data['forms']['basic-form']['fields']['url']['value'] = '/';
            }
        }

        $data['blocksCatalog'] = $catalog;
        $data['layoutsCatalog'] = $layouts;

        return $data;
    }

    protected function validateRecord(Request $request): array
    {
        $formDefinitions = $this->sortedFormDefinitions();
        $rules = FormSchema::validationRulesForDefinitions($this->pluginId(), $formDefinitions);

        $currentSlug = $request->route('pageSlug');
        $currentSlug = $currentSlug instanceof ThemeFileRecord ? $currentSlug->slug : $currentSlug;

        $rules['url'][] = function (string $attribute, mixed $value, Closure $fail) use ($currentSlug): void {
            if (! is_string($value)) {
                return;
            }

            $url = strtolower(trim($value, '/'));

            if ($this->pages->urlExists($url, is_string($currentSlug) ? $currentSlug : null, $this->activeThemeSlug())) {
                $fail('The URL has already been taken for this theme.');
            }
        };
        $rules['sections'] = ['nullable', 'array', $this->sectionsStructureRule()];
        $rules['layout'][] = $this->layoutExistsRule();

        $validated = $request->validate($rules);

        if (isset($validated['url']) && is_string($validated['url'])) {
            $validated['url'] = strtolower(trim($validated['url'], '/'));
        }

        $sections = is_array($validated['sections'] ?? null) ? $validated['sections'] : [];
        $blocksBySlug = $this->blocks->all($this->activeThemeSlug())
            ->keyBy(fn (ThemeFileRecord $block) => $block->slug);

        $processor = new MediaParameterProcessor;
        $sections = $processor->processSections(
            $sections,
            $request,
            function (string $blockSlug) use ($blocksBySlug): array {
                $block = $blocksBySlug->get($blockSlug);

                if ($block === null) {
                    return [];
                }

                $parameters = $block->code['parameters'] ?? [];

                return is_array($parameters) ? $parameters : [];
            }
        );

        $validated['sections'] = $this->normalizeSections($sections);

        return FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutsCatalog(): array
    {
        return $this->layouts->all($this->activeThemeSlug())
            ->sortBy('name')
            ->map(fn (ThemeFileRecord $layout) => [
                'slug' => $layout->slug,
                'name' => $layout->name,
            ])
            ->values()
            ->all();
    }

    protected function layoutExistsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $layouts = $this->layouts->all($this->activeThemeSlug());

            if ($layouts->isEmpty()) {
                $fail('Create at least one layout for this theme before adding pages.');

                return;
            }

            if (! is_string($value) || $value === '') {
                return;
            }

            if ($layouts->first(fn (ThemeFileRecord $layout) => $layout->slug === $value) === null) {
                $fail('The selected layout does not exist for this theme.');
            }
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function blocksCatalog(): array
    {
        return $this->blocks->all($this->activeThemeSlug())
            ->sortBy('name')
            ->map(fn (ThemeFileRecord $block) => [
                'slug' => $block->slug,
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

            $blocksBySlug = $this->blocks->all($this->activeThemeSlug())
                ->keyBy(fn (ThemeFileRecord $block) => $block->slug);

            foreach ($value as $index => $section) {
                if (! is_array($section)) {
                    $fail('Section at index '.$index.' must be an object.');

                    return;
                }

                $blockSlug = $section['block_slug'] ?? null;

                if ($blockSlug === null || $blockSlug === '') {
                    $fail('Section at index '.$index.' must include a block.');

                    return;
                }

                $block = $blocksBySlug->get((string) $blockSlug);

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

                    if ($type === 'repeater') {
                        if ($paramValue === null || $paramValue === '') {
                            continue;
                        }

                        if (! is_array($paramValue)) {
                            $fail('Parameter "'.$name.'" in section '.$index.' must be a list of items.');

                            return;
                        }

                        $fields = is_array($parameter['fields'] ?? null) ? $parameter['fields'] : [];
                        $allowedFieldNames = collect($fields)->pluck('name')->filter()->all();

                        foreach ($paramValue as $rowIndex => $row) {
                            if (! is_array($row)) {
                                $fail('Parameter "'.$name.'" row '.$rowIndex.' in section '.$index.' must be an object.');

                                return;
                            }

                            foreach ($row as $fieldKey => $fieldValue) {
                                if (! in_array($fieldKey, $allowedFieldNames, true)) {
                                    $fail('Parameter "'.$name.'" row '.$rowIndex.' in section '.$index.' has an unknown field "'.$fieldKey.'".');

                                    return;
                                }
                            }

                            foreach ($fields as $field) {
                                if (! is_array($field)) {
                                    continue;
                                }

                                $fieldName = $field['name'] ?? null;
                                $fieldType = $field['type'] ?? 'text';

                                if (! is_string($fieldName) || $fieldName === '') {
                                    continue;
                                }

                                $fieldValue = $row[$fieldName] ?? null;

                                if ($fieldType === 'checkbox') {
                                    continue;
                                }

                                if (MediaParameterProcessor::isMediaType($fieldType)) {
                                    if (! MediaParameterProcessor::validateCompoundValue($fieldValue, $name.'.'.$fieldName, $fail)) {
                                        return;
                                    }

                                    continue;
                                }

                                if (UrlParameterProcessor::isUrlType($fieldType)) {
                                    if (! UrlParameterProcessor::validateCompoundValue($fieldValue, $name.'.'.$fieldName, $fail)) {
                                        return;
                                    }

                                    continue;
                                }

                                if ($fieldValue === null || $fieldValue === '') {
                                    continue;
                                }

                                if ($fieldType === 'number' && ! is_numeric($fieldValue)) {
                                    $fail('Parameter "'.$name.'.'.$fieldName.'" in section '.$index.' row '.$rowIndex.' must be a number.');

                                    return;
                                }

                                if ($fieldType === 'email' && ! filter_var((string) $fieldValue, FILTER_VALIDATE_EMAIL)) {
                                    $fail('Parameter "'.$name.'.'.$fieldName.'" in section '.$index.' row '.$rowIndex.' must be a valid email.');

                                    return;
                                }
                            }
                        }

                        continue;
                    }

                    if ($type === 'checkbox') {
                        continue;
                    }

                    if (MediaParameterProcessor::isMediaType($type)) {
                        if (! MediaParameterProcessor::validateCompoundValue($paramValue, $name, $fail)) {
                            return;
                        }

                        continue;
                    }

                    if (UrlParameterProcessor::isUrlType($type)) {
                        if (! UrlParameterProcessor::validateCompoundValue($paramValue, $name, $fail)) {
                            return;
                        }

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
     * @return list<array{block_slug: string, values: array<string, mixed>}>
     */
    protected function normalizeSections(array $sections): array
    {
        return collect($sections)
            ->filter(fn ($section) => is_array($section) && ! empty($section['block_slug']))
            ->map(function (array $section) {
                $values = $section['values'] ?? [];

                return [
                    'block_slug' => (string) $section['block_slug'],
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

    protected function fileStore(): ThemeFileStore
    {
        return $this->pages;
    }

    protected function viewNamespace(): string
    {
        return 'loom-pages';
    }

    protected function routeRecordKey(): string
    {
        return 'pageSlug';
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
