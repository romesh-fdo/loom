<?php

namespace Loom\Features\PluginBuilder\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Loom\Builder\Blueprint;
use Loom\Builder\BlueprintSynchronizer;
use Loom\Builder\BootstrapIconRegistry;
use Loom\Builder\FieldTypeRegistry;
use Loom\Builder\PluginImporter;
use Loom\Builder\ReservedPluginSlugs;
use Loom\Builder\SecureFileWriter;
use Loom\Builder\TableNames;
use Loom\System\PluginManager;

class PluginBuilderController extends Controller
{
    public function index(): View
    {
        $plugins = collect(app(PluginManager::class)->getPlugins())
            ->map(function ($plugin) {
                $details = $plugin->pluginDetails();
                $nav = $plugin->registerNavigation();

                return [
                    'id' => $plugin->getPluginIdentifier(),
                    'label' => $details['name'] ?? $plugin->getName(),
                    'icon' => $nav[0]['icon'] ?? 'bi-puzzle',
                    'url' => route('loom.plugin-builder.edit', $plugin->getName()),
                ];
            })
            ->values();

        return view('loom-plugin-builder::index', [
            'plugins' => $plugins,
        ]);
    }

    public function create(): View
    {
        return view('loom-plugin-builder::create', [
            'fieldTypes' => FieldTypeRegistry::labels(),
            'tablePrefix' => TableNames::prefix(),
        ]);
    }

    public function icons(Request $request): JsonResponse
    {
        return response()->json([
            'icons' => BootstrapIconRegistry::search($request->string('q')->toString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->fieldRules(includeSlug: true));

        $slug = $validated['plugin_slug'];

        if (ReservedPluginSlugs::isReserved($slug)) {
            return back()
                ->withInput()
                ->with('error', "Plugin slug \"{$slug}\" is reserved for a system feature.");
        }

        if (is_dir(app(SecureFileWriter::class)->pluginRoot($slug))) {
            return back()
                ->withInput()
                ->with('error', "Plugin folder \"{$slug}\" already exists. Open it from the plugin list instead.");
        }

        $definition = $this->buildDefinition($validated, true);
        $blueprint = Blueprint::fromArray(
            $definition,
            $this->pluginIdentifier($slug)
        );

        $result = $this->syncPluginFiles($blueprint, 'Plugin created');

        return redirect()
            ->route('loom.plugin-builder.edit', $slug)
            ->with($result['flash'], $result['text'])
            ->with('migrate_output', $result['migrate_output'] ?? '');
    }

    public function edit(string $pluginSlug): View
    {
        $definition = app(PluginImporter::class)
            ->import($this->pluginIdentifier($pluginSlug))
            ->toArray();

        return view('loom-plugin-builder::edit', [
            'pluginSlug' => $pluginSlug,
            'definition' => $definition,
            'fieldTypes' => FieldTypeRegistry::labels(),
            'tablePrefix' => TableNames::prefix(),
        ]);
    }

    public function update(Request $request, string $pluginSlug): RedirectResponse
    {
        $validated = $request->validate($this->fieldRules(includeSlug: false));

        $existing = app(PluginImporter::class)
            ->import($this->pluginIdentifier($pluginSlug))
            ->toArray();

        $validated['plugin_slug'] = $pluginSlug;

        $definition = $this->buildDefinition(
            $validated,
            (bool) ($existing['is_new'] ?? false),
            $existing
        );

        $blueprint = Blueprint::fromArray(
            $definition,
            $this->pluginIdentifier($pluginSlug)
        );

        $result = $this->syncPluginFiles($blueprint, 'Plugin saved');

        return redirect()
            ->route('loom.plugin-builder.edit', $pluginSlug)
            ->with($result['flash'], $result['text'])
            ->with('migrate_output', $result['migrate_output'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldRules(bool $includeSlug): array
    {
        $rules = [
            'plugin_label' => ['required', 'string', 'max:255'],
            'route_slug' => ['required', 'string', 'regex:/^[a-z][a-z0-9-]*$/'],
            'model_class' => ['required', 'string', 'regex:/^[A-Z][A-Za-z0-9]*$/'],
            'table_name' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/'],
            'plugin_icon' => ['nullable', 'string', 'regex:/^bi-[a-z0-9-]+$/'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.type' => ['required_with:fields', 'string'],
            'fields.*.colClass' => ['nullable', 'string', 'max:64'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.validation_rules.*.rule' => ['nullable', 'string', 'max:255'],
            'fields.*.validation_rules.*.message' => ['nullable', 'string', 'max:500'],
        ];

        if ($includeSlug) {
            $rules['plugin_slug'] = ['required', 'string', 'regex:/^[a-z][a-z0-9-]*$/'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    protected function buildDefinition(array $validated, bool $isNew, ?array $existing = null): array
    {
        $fields = collect($validated['fields'] ?? [])->map(function (array $field) {
            $type = $field['type'] ?? 'text';
            [$validation, $validationMessages] = $this->normalizeFieldValidation($field, $type);

            $normalized = [
                'name' => $field['name'],
                'type' => $type,
                'label' => $field['label'],
                'storage' => 'model',
                'validation' => $validation,
                'colClass' => $field['colClass'] ?? 'col-12',
            ];

            if ($validationMessages !== []) {
                $normalized['validation_messages'] = $validationMessages;
            }

            return $normalized;
        })->all();

        $forms = [];

        if ($fields !== []) {
            $forms[] = [
                'key' => 'basic-form',
                'schema' => 'basic',
                'storage' => 'model',
                'layout' => 'panel',
                'fields' => $fields,
            ];
        }

        $icon = $validated['plugin_icon'] ?? 'bi-box';

        if (! BootstrapIconRegistry::isValid($icon)) {
            $icon = 'bi-box';
        }

        return [
            'is_new' => $isNew,
            'plugin' => [
                'name' => $validated['plugin_slug'],
                'label' => $validated['plugin_label'],
                'route' => $validated['route_slug'],
                'icon' => $icon,
            ],
            'model' => [
                'class' => $validated['model_class'],
                'table' => $this->resolveTableName($validated['table_name'], $isNew, $existing),
            ],
            'forms' => $forms,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $existing
     */
    protected function resolveTableName(string $suffix, bool $isNew, ?array $existing = null): string
    {
        $suffix = strtolower(trim($suffix));
        $existingTable = is_array($existing) ? ($existing['model']['table'] ?? null) : null;

        if ($isNew) {
            return TableNames::applyPrefix($suffix);
        }

        if (is_string($existingTable) && $existingTable !== '' && TableNames::stripPrefix($existingTable) === $suffix) {
            return $existingTable;
        }

        return TableNames::applyPrefix($suffix);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{0: list<string>, 1: array<string, string>}
     */
    protected function normalizeFieldValidation(array $field, string $type): array
    {
        $validation = [];
        $validationMessages = [];

        foreach ($field['validation_rules'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $rule = trim((string) ($entry['rule'] ?? ''));

            if ($rule === '') {
                continue;
            }

            $validation[] = $rule;

            $message = trim((string) ($entry['message'] ?? ''));

            if ($message !== '') {
                $validationMessages[Str::before($rule, ':')] = $message;
            }
        }

        if ($validation === []) {
            $validation = FieldTypeRegistry::defaultValidation($type);
        }

        return [$validation, $validationMessages];
    }

    /**
     * @return array{flash: string, text: string, migrate_output?: string}
     */
    protected function syncPluginFiles(Blueprint $blueprint, string $savedMessage): array
    {
        try {
            $result = app(BlueprintSynchronizer::class)->sync($blueprint);

            if ($result['migration_failed']) {
                return [
                    'flash' => 'warning',
                    'text' => $savedMessage.', plugin files updated, but migration may have failed.',
                    'migrate_output' => $result['migrate_output'],
                ];
            }

            $text = $savedMessage.', plugin files updated';

            if (($result['migrate_output'] ?? '') !== '') {
                $text .= ', and migrations ran';
            }

            $text .= '.';

            return [
                'flash' => 'success',
                'text' => $text,
                'migrate_output' => $result['migrate_output'] ?? '',
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'flash' => 'warning',
                'text' => $savedMessage.', but plugin files could not be updated: '.$e->getMessage(),
            ];
        }
    }

    protected function pluginIdentifier(string $slug): string
    {
        return 'loom.'.$slug;
    }
}
