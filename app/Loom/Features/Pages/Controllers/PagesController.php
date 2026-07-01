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
use Loom\Support\ThemeContent\PageEntityImportsComposer;
use Loom\Support\ThemeContent\PageLayoutFieldsComposer;
use Loom\Support\ThemeContent\PageStore;
use Loom\Support\ThemeContent\PageUrlPattern;
use Loom\Support\ThemeContent\SegmentStore;
use Loom\Support\ThemeContent\ThemeCodeTemplate;
use Loom\Support\ThemeContent\ThemeDirectiveParser;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemeFileStore;
use Loom\Support\UrlParameterProcessor;
use Loom\System\PluginManager;

class PagesController extends ThemeFileResourceController
{
    public function __construct(
        protected PageStore $pages,
        protected BlockStore $blocks,
        protected LayoutStore $layouts,
        protected SegmentStore $segments,
        protected PluginManager $pluginManager,
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

        $functionsCatalog = $this->pluginManager->getFunctionsCatalog();
        $entityImports = [];

        if ($record !== null && is_array($record->entity_imports ?? null)) {
            $entityImports = array_values($record->entity_imports);
        }

        if (isset($data['forms']['basic-form']['fields']['entity_imports'])) {
            $data['forms']['basic-form']['fields']['entity_imports']['pluginsFunctionsCatalog'] = $functionsCatalog;
            $recordUrl = $record !== null && is_string($record->url ?? null) ? (string) $record->url : '';
            $data['forms']['basic-form']['fields']['entity_imports']['pageUrl'] = $recordUrl;
        }

        if (isset($data['forms']['basic-form']['fields']['layout_fields'])) {
            $data['forms']['basic-form']['fields']['layout_fields']['layoutsCatalog'] = $layouts;
            $data['forms']['basic-form']['fields']['layout_fields']['entityImports'] = $entityImports;
            $data['forms']['basic-form']['fields']['layout_fields']['pluginsFunctionsCatalog'] = $functionsCatalog;
        }

        if (isset($data['forms']['basic-form']['fields']['layout'])) {
            $data['forms']['basic-form']['fields']['layout']['options'] = $layoutOptions;

            $currentValue = $data['forms']['basic-form']['fields']['layout']['value'] ?? '';

            if ($record === null && ($currentValue === '' || $currentValue === null) && $layoutOptions !== []) {
                $data['forms']['basic-form']['fields']['layout']['value'] = $layoutOptions[0]['value'];
                $currentValue = $layoutOptions[0]['value'];
            }

            if (isset($data['forms']['basic-form']['fields']['layout_fields'])) {
                $data['forms']['basic-form']['fields']['layout_fields']['selectedLayout'] = $currentValue;
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
        $data['pluginsFunctionsCatalog'] = $functionsCatalog;
        $data['entityImports'] = $entityImports;

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
        $rules['entity_imports'] = ['nullable', 'array', $this->entityImportsStructureRule($request)];
        $rules['layout_fields'] = ['nullable', 'array', $this->layoutFieldsStructureRule()];
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

        $entityImports = is_array($validated['entity_imports'] ?? null) ? $validated['entity_imports'] : [];
        $validated['entity_imports'] = $this->normalizeEntityImports($entityImports);

        $layoutFields = is_array($validated['layout_fields'] ?? null) ? $validated['layout_fields'] : [];
        $layoutSlug = (string) ($validated['layout'] ?? '');
        $layoutFields = $processor->processLayoutFields(
            $layoutFields,
            $request,
            fn (string $segmentPath) => $this->parametersForLayoutSegment($layoutSlug, $segmentPath)
        );
        $validated['layout_fields'] = $this->normalizeLayoutFields($layoutFields, $layoutSlug, $validated['entity_imports']);

        return FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutsCatalog(): array
    {
        $themeSlug = $this->activeThemeSlug();

        return $this->layouts->all($themeSlug)
            ->sortBy('name')
            ->map(function (ThemeFileRecord $layout) use ($themeSlug) {
                $template = ThemeCodeTemplate::template($layout->code ?? null);
                $segments = [];

                foreach (ThemeDirectiveParser::parseSegmentDirectives($template) as $directive) {
                    $path = $directive['path'];
                    $segment = $this->segments->find($path, $themeSlug);

                    if ($segment === null) {
                        continue;
                    }

                    $segmentCode = is_array($segment->code ?? null) ? $segment->code : [];
                    $parameters = is_array($segmentCode['parameters'] ?? null) ? $segmentCode['parameters'] : [];

                    if ($parameters === []) {
                        continue;
                    }

                    $segments[] = [
                        'path' => $path,
                        'name' => (string) ($segment->name ?? $path),
                        'parameters' => $parameters,
                    ];
                }

                return [
                    'slug' => $layout->slug,
                    'name' => $layout->name,
                    'segments' => $segments,
                ];
            })
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

    protected function entityImportsStructureRule(Request $request): Closure
    {
        $pageUrl = strtolower(trim((string) $request->input('url', ''), '/'));
        $urlPlaceholders = PageUrlPattern::extractPlaceholders($pageUrl);

        return function (string $attribute, mixed $value, Closure $fail) use ($urlPlaceholders): void {
            if (! is_array($value)) {
                $fail('Entity imports must be an array.');

                return;
            }

            $seenVariables = [];

            foreach (array_values($value) as $index => $import) {
                if (! is_array($import)) {
                    $fail('Entity import #'.($index + 1).' must be an object.');

                    return;
                }

                $variable = trim((string) ($import['variable'] ?? ''));
                $plugin = trim((string) ($import['plugin'] ?? ''));
                $function = trim((string) ($import['function'] ?? ''));

                if ($variable === '' || $plugin === '' || $function === '') {
                    $fail('Entity import #'.($index + 1).' requires a variable, plugin, and function.');

                    return;
                }

                if (! PageEntityImportsComposer::isValidVariableName($variable)) {
                    $fail('Entity import variable "'.$variable.'" must be a valid PHP identifier.');

                    return;
                }

                if (isset($seenVariables[$variable])) {
                    $fail('Entity import variable "'.$variable.'" is used more than once.');

                    return;
                }

                $seenVariables[$variable] = true;

                $definition = $this->pluginManager->getFunctionDefinition($plugin, $function);

                if ($definition === null) {
                    $fail('Entity import #'.($index + 1).' references an unknown plugin function.');

                    return;
                }

                $parameters = is_array($definition['parameters'] ?? null) ? $definition['parameters'] : [];
                $submitted = is_array($import['parameters'] ?? null) ? $import['parameters'] : [];

                foreach ($parameters as $parameter) {
                    if (! is_array($parameter)) {
                        continue;
                    }

                    $paramName = (string) ($parameter['name'] ?? '');

                    if ($paramName === '') {
                        continue;
                    }

                    $binding = $submitted[$paramName] ?? null;

                    if (! is_array($binding)) {
                        if (($parameter['required'] ?? false) === true) {
                            $fail('Entity import "'.$variable.'" is missing parameter "'.$paramName.'".');
                        }

                        continue;
                    }

                    $mode = (string) ($binding['mode'] ?? 'static');

                    if ($mode === 'path_param') {
                        $param = trim((string) ($binding['param'] ?? ''));

                        if ($param === '') {
                            $fail('Entity import "'.$variable.'" parameter "'.$paramName.'" requires a path parameter name.');

                            return;
                        }

                        if (! in_array($param, $urlPlaceholders, true)) {
                            $fail('Entity import "'.$variable.'" parameter "'.$paramName.'" path parameter "'.$param.'" must exist in the page URL.');

                            return;
                        }
                    }

                    if ($mode === 'query_param') {
                        $param = trim((string) ($binding['param'] ?? ''));

                        if ($param === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $param)) {
                            $fail('Entity import "'.$variable.'" parameter "'.$paramName.'" requires a valid query parameter name.');

                            return;
                        }
                    }

                    if ($mode === 'url_segment' && (int) ($binding['segment'] ?? 0) < 1) {
                        $fail('Entity import "'.$variable.'" parameter "'.$paramName.'" requires a valid URL segment index.');

                        return;
                    }

                    if ($mode === 'route_param' && trim((string) ($binding['param'] ?? '')) === '') {
                        $fail('Entity import "'.$variable.'" parameter "'.$paramName.'" requires a route parameter name.');

                        return;
                    }
                }
            }
        };
    }

    /**
     * @param  array<int, mixed>  $entityImports
     * @return list<array<string, mixed>>
     */
    protected function normalizeEntityImports(array $entityImports): array
    {
        $normalized = [];

        foreach (array_values($entityImports) as $import) {
            if (! is_array($import)) {
                continue;
            }

            $variable = trim((string) ($import['variable'] ?? ''));
            $plugin = trim((string) ($import['plugin'] ?? ''));
            $function = trim((string) ($import['function'] ?? ''));

            if ($variable === '' || $plugin === '' || $function === '') {
                continue;
            }

            $definition = $this->pluginManager->getFunctionDefinition($plugin, $function);
            $parameters = [];

            if ($definition !== null) {
                foreach ($definition['parameters'] ?? [] as $parameter) {
                    if (! is_array($parameter)) {
                        continue;
                    }

                    $paramName = (string) ($parameter['name'] ?? '');

                    if ($paramName === '') {
                        continue;
                    }

                    $binding = is_array($import['parameters'][$paramName] ?? null)
                        ? $import['parameters'][$paramName]
                        : [];
                    $mode = (string) ($binding['mode'] ?? 'static');

                    if (($parameter['dynamic'] ?? false) === true) {
                        $parameters[$paramName] = match ($mode) {
                            'path_param' => ['mode' => 'path_param', 'param' => trim((string) ($binding['param'] ?? ''))],
                            'query_param' => ['mode' => 'query_param', 'param' => trim((string) ($binding['param'] ?? ''))],
                            'url_segment' => ['mode' => 'url_segment', 'segment' => max(1, (int) ($binding['segment'] ?? 1))],
                            'route_param' => ['mode' => 'route_param', 'param' => trim((string) ($binding['param'] ?? ''))],
                            default => ['mode' => 'static', 'value' => (string) ($binding['value'] ?? '')],
                        };
                    } else {
                        $parameters[$paramName] = [
                            'mode' => 'static',
                            'value' => (string) ($binding['value'] ?? ''),
                        ];
                    }
                }
            }

            $normalized[] = [
                'variable' => ltrim($variable, '$'),
                'plugin' => $plugin,
                'function' => $function,
                'parameters' => $parameters,
            ];
        }

        return $normalized;
    }

    protected function layoutFieldsStructureRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value)) {
                $fail('Layout fields must be an array.');

                return;
            }

            $layoutSlug = (string) request()->input('layout', '');

            foreach ($value as $segmentPath => $fields) {
                if (! is_string($segmentPath) || $segmentPath === '') {
                    $fail('Layout fields contain an invalid segment path.');

                    return;
                }

                if (! is_array($fields)) {
                    $fail('Layout fields for segment "'.$segmentPath.'" must be an object.');

                    return;
                }

                $parameters = $this->parametersForLayoutSegment($layoutSlug, $segmentPath);
                $allowedNames = collect($parameters)->pluck('name')->all();

                foreach ($fields as $fieldName => $fieldValue) {
                    if ($fieldName === '_mode' || str_ends_with((string) $fieldName, '._mode')) {
                        continue;
                    }

                    if (! in_array($fieldName, $allowedNames, true)) {
                        $fail('Layout segment "'.$segmentPath.'" has an unknown field "'.$fieldName.'".');

                        return;
                    }

                    if (is_array($fieldValue) && $this->isSubmittedDynamicLayoutField($fieldValue)) {
                        if (! $this->validateDynamicLayoutField($fieldValue, $segmentPath, $fieldName, $fail)) {
                            return;
                        }

                        continue;
                    }

                    if (is_array($fieldValue) && array_key_exists('static', $fieldValue)) {
                        $fieldValue = $fieldValue['static'] ?? null;
                    }

                    $parameter = collect($parameters)->first(fn ($item) => is_array($item) && ($item['name'] ?? null) === $fieldName);

                    if (! is_array($parameter)) {
                        continue;
                    }

                    if (! $this->validateParameterValue($parameter, $fieldValue, $segmentPath.'.'.$fieldName, $fail)) {
                        return;
                    }
                }
            }
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parametersForLayoutSegment(string $layoutSlug, string $segmentPath): array
    {
        $layout = $this->layouts->find($layoutSlug, $this->activeThemeSlug());

        if ($layout === null) {
            return [];
        }

        $template = ThemeCodeTemplate::template($layout->code ?? null);
        $usesSegment = collect(ThemeDirectiveParser::parseSegmentDirectives($template))
            ->contains(fn (array $directive) => $directive['path'] === $segmentPath);

        if (! $usesSegment) {
            return [];
        }

        $segment = $this->segments->find($segmentPath, $this->activeThemeSlug());

        if ($segment === null) {
            return [];
        }

        $segmentCode = is_array($segment->code ?? null) ? $segment->code : [];
        $parameters = $segmentCode['parameters'] ?? [];

        return is_array($parameters) ? $parameters : [];
    }

    /**
     * @param  array<string, mixed>  $layoutFields
     * @param  list<array<string, mixed>>  $entityImports
     * @return array<string, array<string, mixed>>
     */
    protected function normalizeLayoutFields(array $layoutFields, string $layoutSlug, array $entityImports = []): array
    {
        $normalized = [];
        $importVariables = collect($entityImports)->pluck('variable')->filter()->all();

        foreach ($layoutFields as $segmentPath => $fields) {
            if (! is_string($segmentPath) || $segmentPath === '' || ! is_array($fields)) {
                continue;
            }

            $parameters = $this->parametersForLayoutSegment($layoutSlug, $segmentPath);
            $allowedNames = collect($parameters)->pluck('name')->all();
            $segmentValues = [];

            foreach ($fields as $fieldName => $fieldValue) {
                if (! is_string($fieldName) || ! in_array($fieldName, $allowedNames, true)) {
                    continue;
                }

                if (is_array($fieldValue)) {
                    if ($this->isSubmittedDynamicLayoutField($fieldValue)) {
                        $import = trim((string) ($fieldValue['import'] ?? ''));
                        $field = trim((string) ($fieldValue['field'] ?? ''));

                        if ($import === '' && isset($fieldValue['dynamic'])) {
                            [$import, $field] = PageLayoutFieldsComposer::splitDynamicPath((string) $fieldValue['dynamic']);
                        }

                        if ($import !== '' && $field !== '' && in_array($import, $importVariables, true)) {
                            $segmentValues[$fieldName] = PageLayoutFieldsComposer::normalizeDynamicField([
                                'import' => $import,
                                'field' => $field,
                            ]);
                        }

                        continue;
                    }

                    if (array_key_exists('static', $fieldValue)) {
                        $fieldValue = $fieldValue['static'];
                    }
                }

                if ($fieldValue === null || $fieldValue === '' || $fieldValue === []) {
                    continue;
                }

                $segmentValues[$fieldName] = $fieldValue;
            }

            if ($segmentValues !== []) {
                $normalized[$segmentPath] = $segmentValues;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $parameter
     */
    protected function validateParameterValue(array $parameter, mixed $paramValue, string $name, Closure $fail): bool
    {
        $type = $parameter['type'] ?? 'text';

        if ($type === 'checkbox') {
            return true;
        }

        if (MediaParameterProcessor::isMediaType($type)) {
            return MediaParameterProcessor::validateCompoundValue($paramValue, $name, $fail);
        }

        if (UrlParameterProcessor::isUrlType($type)) {
            return UrlParameterProcessor::validateCompoundValue($paramValue, $name, $fail);
        }

        if ($paramValue === null || $paramValue === '') {
            return true;
        }

        if ($type === 'number' && ! is_numeric($paramValue)) {
            $fail('Parameter "'.$name.'" must be a number.');

            return false;
        }

        if ($type === 'email' && ! filter_var((string) $paramValue, FILTER_VALIDATE_EMAIL)) {
            $fail('Parameter "'.$name.'" must be a valid email.');

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $fieldValue
     */
    protected function isSubmittedDynamicLayoutField(array $fieldValue): bool
    {
        return PageLayoutFieldsComposer::isSubmittedDynamicField($fieldValue);
    }

    /**
     * @param  array<string, mixed>  $fieldValue
     */
    protected function validateDynamicLayoutField(array $fieldValue, string $segmentPath, string $fieldName, Closure $fail): bool
    {
        $import = trim((string) ($fieldValue['import'] ?? ''));
        $field = trim((string) ($fieldValue['field'] ?? ''));

        if ($import === '' && isset($fieldValue['dynamic'])) {
            [$import, $field] = PageLayoutFieldsComposer::splitDynamicPath((string) $fieldValue['dynamic']);
        }

        if ($import === '' || $field === '') {
            $fail('Dynamic field "'.$segmentPath.'.'.$fieldName.'" requires both an import and a return field.');

            return false;
        }

        $entityImports = $this->normalizeEntityImports(
            is_array(request()->input('entity_imports')) ? request()->input('entity_imports') : []
        );

        $selectedImport = collect($entityImports)->first(
            fn (array $item) => ($item['variable'] ?? '') === $import
        );

        if ($selectedImport === null) {
            $fail('Dynamic field "'.$segmentPath.'.'.$fieldName.'" references unknown import "'.$import.'".');

            return false;
        }

        $definition = $this->pluginManager->getFunctionDefinition(
            (string) ($selectedImport['plugin'] ?? ''),
            (string) ($selectedImport['function'] ?? '')
        );

        if ($definition === null) {
            $fail('Dynamic field "'.$segmentPath.'.'.$fieldName.'" references an invalid import function.');

            return false;
        }

        $allowedFields = collect($definition['returns'] ?? [])
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->all();

        if (! in_array($field, $allowedFields, true)) {
            $fail('Dynamic field "'.$segmentPath.'.'.$fieldName.'" references unknown return field "'.$field.'".');

            return false;
        }

        return true;
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
