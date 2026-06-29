<?php

namespace Loom\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Loom\Features\Contracts\FormModule;
use Loom\Http\Controllers\Concerns\RespondsToAjaxFormSave;
use Loom\Http\Controllers\Concerns\ResolvesActiveTheme;
use Loom\Support\FormSchema;
use Loom\Support\ModuleResolver;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemeFileStore;

abstract class ThemeFileResourceController extends Controller
{
    use ResolvesActiveTheme;
    use RespondsToAjaxFormSave;

    abstract protected function pluginId(): string;

    abstract protected function fileStore(): ThemeFileStore;

    abstract protected function viewNamespace(): string;

    abstract protected function routeRecordKey(): string;

    abstract protected function indexRoute(): string;

    abstract protected function resourceLabel(): string;

    public function create(): View
    {
        return view($this->createView(), $this->formViewData());
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $record = $this->fileStore()->create($this->validateRecord($request));

        $this->afterStore($record);

        return $this->savedResponse($request, 'created', $record);
    }

    public function edit(Request $request): View
    {
        $record = $this->resolveRouteRecord($request);

        return view($this->editView(), array_merge(
            [$this->viewRecordKey() => $record],
            $this->formViewData($record)
        ));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $slug = $this->routeSlug($request);
        $record = $this->fileStore()->update($slug, $this->validateRecord($request));

        $this->afterUpdate($record);

        return $this->savedResponse($request, 'updated', $record);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->fileStore()->delete($this->routeSlug($request));

        return redirect()
            ->route($this->indexRoute())
            ->with('success', $this->flashMessage('deleted'));
    }

    protected function createView(): string
    {
        return $this->viewNamespace().'::create';
    }

    protected function editView(): string
    {
        return $this->viewNamespace().'::edit';
    }

    protected function defaultSchema(): string
    {
        return 'basic';
    }

    protected function formViewData(?ThemeFileRecord $record = null): array
    {
        $formDefinitions = $this->sortedFormDefinitions();
        $forms = [];

        foreach ($formDefinitions as $key => $definition) {
            $schema = FormSchema::loadForDefinition($this->pluginId(), $definition, $record);
            $forms[$key] = [
                'meta' => $schema['meta'],
                'form' => $schema['form'],
                'layout' => $schema['layout'],
                'fields' => $schema['fields'],
            ];
        }

        $firstForm = reset($forms) ?: null;

        return [
            'form' => $firstForm['form'] ?? FormSchema::load($this->pluginId(), $this->defaultSchema())['form'],
            'forms' => $forms,
        ];
    }

    protected function validateRecord(Request $request): array
    {
        $formDefinitions = $this->sortedFormDefinitions();

        $validated = $request->validate(
            FormSchema::validationRulesForDefinitions($this->pluginId(), $formDefinitions)
        );

        return FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
    }

    protected function module(): FormModule
    {
        $module = ModuleResolver::resolve($this->pluginId());

        if ($module === null) {
            abort(500, "Module not found: {$this->pluginId()}");
        }

        return $module;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function sortedFormDefinitions(): array
    {
        $definitions = $this->module()->registerForms();

        uasort(
            $definitions,
            fn (array $a, array $b) => (FormSchema::meta($this->pluginId(), $a['schema'])['order'] ?? 500)
                <=> (FormSchema::meta($this->pluginId(), $b['schema'])['order'] ?? 500)
        );

        return $definitions;
    }

    protected function resolveRouteRecord(Request $request): ThemeFileRecord
    {
        $slug = $this->routeSlug($request);

        try {
            return $this->fileStore()->findOrFail($slug, $this->activeThemeSlug());
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }

    protected function routeSlug(Request $request): string
    {
        $record = $request->route($this->routeRecordKey());

        if ($record instanceof ThemeFileRecord) {
            return $record->slug;
        }

        return (string) $record;
    }

    protected function viewRecordKey(): string
    {
        $key = $this->routeRecordKey();

        if (str_ends_with($key, 'Slug')) {
            return lcfirst(substr($key, 0, -4));
        }

        return $key;
    }

    protected function flashMessage(string $action): string
    {
        $label = strtolower($this->resourceLabel());

        return match ($action) {
            'created' => ucfirst($label).' created successfully.',
            'updated' => ucfirst($label).' updated successfully.',
            'deleted' => ucfirst($label).' deleted successfully.',
            default => 'Changes saved successfully.',
        };
    }

    protected function afterStore(ThemeFileRecord $record): void
    {
        //
    }

    protected function afterUpdate(ThemeFileRecord $record): void
    {
        //
    }
}
