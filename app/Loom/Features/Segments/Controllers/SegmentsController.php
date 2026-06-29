<?php

namespace Loom\Features\Segments\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Http\Controllers\Concerns\ValidatesDynamicCode;
use Loom\Http\Controllers\ThemeFileResourceController;
use Loom\Support\FormSchema;
use Loom\Support\ThemeContent\SegmentStore;
use Loom\Support\ThemeContent\ThemeFileRecord;
use Loom\Support\ThemeContent\ThemeFileStore;

class SegmentsController extends ThemeFileResourceController
{
    use ValidatesDynamicCode;

    public function __construct(
        protected SegmentStore $segments,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 24) ?? 24);
        $theme = $this->activeTheme();
        $slotLabels = $this->slotLabels($theme);

        $segments = $this->segments->paginate(
            $search->isNotEmpty() ? $search->toString() : null,
            $perPage,
            max(1, (int) $request->input('page', 1)),
            $this->activeThemeSlug()
        );

        return view('loom-segments::index', [
            'segments' => $segments,
            'search' => $search->toString(),
            'slotLabels' => $slotLabels,
        ]);
    }

    protected function formViewData(?ThemeFileRecord $record = null): array
    {
        $data = parent::formViewData($record);
        $theme = $this->activeTheme();
        $slots = $this->slotOptions($theme);

        if (isset($data['forms']['basic-form']['fields']['slot'])) {
            $data['forms']['basic-form']['fields']['slot']['options'] = $slots;
        }

        $parameters = [];
        $values = [];

        if ($record !== null) {
            $code = $record->code ?? [];
            $parameters = is_array($code['parameters'] ?? null) ? $code['parameters'] : [];
            $values = is_array($record->values ?? null) ? $record->values : [];
        }

        $data['segmentParameters'] = $parameters;
        $data['segmentValues'] = $values;

        return $data;
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
        $rules['values'] = ['nullable', 'array'];
        $rules['enabled'] = ['nullable', 'boolean'];

        $currentSlug = $request->route('segmentSlug');
        $currentSlug = $currentSlug instanceof ThemeFileRecord ? $currentSlug->slug : $currentSlug;

        $rules['slot'][] = function (string $attribute, mixed $value, Closure $fail) use ($currentSlug): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $allowed = array_keys($this->slotOptions($this->activeTheme()));

            if ($allowed !== [] && ! in_array($value, $allowed, true)) {
                $fail('The selected slot is not valid for this theme.');

                return;
            }

            if ($this->segments->slotExists($value, is_string($currentSlug) ? $currentSlug : null, $this->activeThemeSlug())) {
                $fail('A segment already exists for this slot.');
            }
        };

        $validated = $request->validate($rules);

        if (isset($validated['code']) && is_string($validated['code'])) {
            $validated['code'] = json_decode($validated['code'], true);
        }

        $validated['enabled'] = $request->boolean('enabled', $currentSlug === null);
        $values = is_array($validated['values'] ?? null) ? $validated['values'] : [];

        $mapped = FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());
        $mapped['values'] = $values;

        return $mapped;
    }

    /**
     * @param  array<string, mixed>|null  $theme
     * @return array<string, string>
     */
    protected function slotOptions(?array $theme): array
    {
        $slots = $theme['segment_slots'] ?? [];

        if (! is_array($slots)) {
            return [];
        }

        $options = [];

        foreach ($slots as $key => $meta) {
            if (! is_string($key)) {
                continue;
            }

            $label = is_array($meta) ? ($meta['label'] ?? $key) : (string) $meta;
            $options[$key] = $label;
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>|null  $theme
     * @return array<string, string>
     */
    protected function slotLabels(?array $theme): array
    {
        return $this->slotOptions($theme);
    }

    protected function pluginId(): string
    {
        return 'loom.segments';
    }

    protected function fileStore(): ThemeFileStore
    {
        return $this->segments;
    }

    protected function viewNamespace(): string
    {
        return 'loom-segments';
    }

    protected function routeRecordKey(): string
    {
        return 'segmentSlug';
    }

    protected function indexRoute(): string
    {
        return 'loom.segments.index';
    }

    protected function resourceLabel(): string
    {
        return 'Segment';
    }
}
