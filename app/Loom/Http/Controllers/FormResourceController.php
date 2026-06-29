<?php

namespace Loom\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Features\Contracts\FormModule;
use Loom\Http\Controllers\Concerns\RespondsToAjaxFormSave;
use Loom\Support\FormSchema;
use Loom\Support\ModuleResolver;

abstract class FormResourceController extends Controller
{
    use RespondsToAjaxFormSave;
    abstract protected function pluginId(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract protected function viewNamespace(): string;

    abstract protected function routeModelKey(): string;

    abstract protected function indexRoute(): string;

    abstract protected function resourceLabel(): string;

    public function create(): View
    {
        return view($this->createView(), $this->formViewData());
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $record = $this->modelClass()::create($this->validateRecord($request));

        $this->afterStore($record);

        return $this->savedResponse($request, 'created', $record);
    }

    public function edit(Request $request): View
    {
        $record = $this->resolveRouteModel($request);

        return view($this->editView(), array_merge(
            [$this->routeModelKey() => $record],
            $this->formViewData($record)
        ));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $record = $this->resolveRouteModel($request);
        $record->update($this->validateRecord($request));

        $this->afterUpdate($record);

        return $this->savedResponse($request, 'updated', $record);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->resolveRouteModel($request)->delete();

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

    protected function formViewData(?Model $record = null): array
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

    protected function plugin(): FormModule
    {
        return $this->module();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function sortedFormDefinitions(): array
    {
        $definitions = $this->plugin()->registerForms();

        uasort(
            $definitions,
            fn (array $a, array $b) => (FormSchema::meta($this->pluginId(), $a['schema'])['order'] ?? 500)
                <=> (FormSchema::meta($this->pluginId(), $b['schema'])['order'] ?? 500)
        );

        return $definitions;
    }

    protected function resolveRouteModel(Request $request): Model
    {
        $record = $request->route($this->routeModelKey());

        if ($record instanceof Model) {
            return $record;
        }

        return $this->modelClass()::findOrFail($record);
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

    protected function afterStore(Model $record): void
    {
        //
    }

    protected function afterUpdate(Model $record): void
    {
        //
    }
}
