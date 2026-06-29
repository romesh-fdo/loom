<?php

namespace Loom\Features\Blocks\Controllers;

use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Loom\Features\Blocks\Models\Block;
use Loom\Http\Controllers\Concerns\ScopesToActiveTheme;
use Loom\Http\Controllers\FormResourceController;
use Loom\Support\FormSchema;

class BlocksController extends FormResourceController
{
    use ScopesToActiveTheme;

    private const ALLOWED_PARAMETER_TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'select',
        'checkbox',
        'color',
    ];

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim();
        $perPage = (int) ($this->module()->getConfig('per_page', 12) ?? 12);

        $blocks = $this->themedQuery()
            ->when($search->isNotEmpty(), fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

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
            $this->blockCodeStructureRule(),
        ];

        $validated = $request->validate($rules);

        if (isset($validated['code']) && is_string($validated['code'])) {
            $validated['code'] = json_decode($validated['code'], true);
        }

        return $this->withThemeSlug(
            FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId()),
            $request
        );
    }

    protected function blockCodeStructureRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $decoded = json_decode((string) $value, true);

            if (! is_array($decoded)) {
                $fail('The code field must be a valid JSON object.');

                return;
            }

            if (! isset($decoded['template']) || ! is_string($decoded['template'])) {
                $fail('The code template is required.');

                return;
            }

            if (trim($decoded['template']) === '') {
                $fail('The code template cannot be empty.');

                return;
            }

            if (! isset($decoded['parameters']) || ! is_array($decoded['parameters'])) {
                $fail('The code parameters must be an array.');

                return;
            }

            $names = [];

            foreach ($decoded['parameters'] as $index => $parameter) {
                if (! is_array($parameter)) {
                    $fail('Parameter at index '.$index.' must be an object.');

                    return;
                }

                foreach (['name', 'label', 'type'] as $key) {
                    if (! isset($parameter[$key]) || ! is_string($parameter[$key]) || $parameter[$key] === '') {
                        $fail('Parameter at index '.$index.' is missing a valid '.$key.'.');

                        return;
                    }
                }

                if (! preg_match('/^[a-z][a-z0-9_]*$/', $parameter['name'])) {
                    $fail('Parameter name "'.$parameter['name'].'" is invalid.');

                    return;
                }

                if (in_array($parameter['name'], $names, true)) {
                    $fail('Parameter name "'.$parameter['name'].'" is duplicated.');

                    return;
                }

                $names[] = $parameter['name'];

                if (! in_array($parameter['type'], self::ALLOWED_PARAMETER_TYPES, true)) {
                    $fail('Parameter type "'.$parameter['type'].'" is not allowed.');

                    return;
                }
            }
        };
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
