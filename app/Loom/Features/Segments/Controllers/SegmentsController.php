<?php

namespace Loom\Features\Segments\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Loom\Http\Controllers\Concerns\ValidatesDynamicCode;
use Loom\Http\Controllers\ThemeFileResourceController;
use Loom\Support\FormSchema;
use Loom\Support\ThemeContent\SegmentPath;
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
        return view('loom-segments::index', [
            'initialSegment' => $request->string('segment')->toString(),
            'initialCreate' => $request->boolean('create'),
            'initialFolder' => SegmentPath::normalize($request->string('folder')->toString()),
        ]);
    }

    public function tree(): JsonResponse
    {
        return response()->json([
            'tree' => $this->segments->tree($this->activeThemeSlug()),
        ]);
    }

    public function formCreate(Request $request): View
    {
        $folder = SegmentPath::normalize($request->string('folder')->toString());

        if ($folder !== '') {
            SegmentPath::validate($folder);
        }

        return view('loom-segments::_form-panel', array_merge(
            $this->formViewData(),
            [
                'folder' => $folder,
                'panelMode' => true,
            ]
        ));
    }

    public function formEdit(Request $request): View
    {
        $record = $this->resolveRouteRecord($request);

        return view('loom-segments::_form-panel', array_merge(
            [$this->viewRecordKey() => $record],
            $this->formViewData($record),
            ['panelMode' => true]
        ));
    }

    public function redirectCreate(): RedirectResponse
    {
        return redirect()->route($this->indexRoute(), array_filter([
            'create' => 1,
            'folder' => request()->string('folder')->toString() ?: null,
        ]));
    }

    public function redirectEdit(Request $request): RedirectResponse
    {
        return redirect()->route($this->indexRoute(), [
            'segment' => $this->routeSlug($request),
        ]);
    }

    public function storeFolder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        try {
            $path = SegmentPath::normalize($validated['path']);
            $this->segments->createFolder($path, $this->activeThemeSlug());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Folder created.',
            'path' => $path,
        ]);
    }

    public function updateFolder(Request $request, string $folderPath): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        try {
            $from = SegmentPath::normalize($folderPath);
            $to = SegmentPath::normalize($validated['path']);
            $this->segments->renameFolder($from, $to, $this->activeThemeSlug());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Folder renamed.',
            'path' => $to,
        ]);
    }

    public function destroyFolder(string $folderPath): JsonResponse
    {
        try {
            $path = SegmentPath::normalize($folderPath);
            $this->segments->deleteFolder($path, $this->activeThemeSlug());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Folder deleted.']);
    }

    public function panelDestroy(Request $request, string $segmentSlug): JsonResponse
    {
        $this->segments->delete($segmentSlug, $this->activeThemeSlug());

        return response()->json(['message' => $this->flashMessage('deleted')]);
    }

    public function panelMove(Request $request, string $segmentSlug): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $from = SegmentPath::normalize($segmentSlug);
            $to = SegmentPath::normalize($validated['path']);
            $name = isset($validated['name']) && is_string($validated['name']) ? trim($validated['name']) : null;
            $this->segments->moveSegment($from, $to, $this->activeThemeSlug(), $name !== '' ? $name : null);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => SegmentPath::dirname($from) === SegmentPath::dirname($to)
                ? 'Segment renamed.'
                : 'Segment moved.',
            'slug' => $to,
        ]);
    }

    protected function formViewData(?ThemeFileRecord $record = null): array
    {
        $data = parent::formViewData($record);

        if ($record === null) {
            $data['segmentCreateDefaults'] = [
                'code' => json_encode([
                    'template' => '<div></div>',
                    'parameters' => [],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        return $data;
    }

    protected function validateRecord(Request $request): array
    {
        if ($request->route('segmentSlug') === null && is_string($request->input('code'))) {
            $decoded = json_decode($request->input('code'), true);

            if (is_array($decoded) && trim((string) ($decoded['template'] ?? '')) === '') {
                $decoded['template'] = '<div></div>';
                $decoded['parameters'] = is_array($decoded['parameters'] ?? null) ? $decoded['parameters'] : [];
                $request->merge(['code' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }

        $formDefinitions = $this->sortedFormDefinitions();
        $rules = FormSchema::validationRulesForDefinitions($this->pluginId(), $formDefinitions);

        $rules['code'] = [
            'required',
            'json',
            $this->dynamicCodeStructureRule(),
        ];
        $rules['enabled'] = ['nullable', 'boolean'];
        $rules['folder'] = ['nullable', 'string', 'max:255'];

        $validated = $request->validate($rules);

        if (isset($validated['code']) && is_string($validated['code'])) {
            $validated['code'] = json_decode($validated['code'], true);
        }

        $currentSlug = $request->route('segmentSlug');
        $currentSlug = $currentSlug instanceof ThemeFileRecord ? $currentSlug->slug : $currentSlug;

        $validated['enabled'] = $request->boolean('enabled', $currentSlug === null);

        $mapped = FormSchema::mapValidatedToModel($validated, $formDefinitions, $this->pluginId());

        if (isset($validated['folder']) && is_string($validated['folder']) && $validated['folder'] !== '') {
            $mapped['folder'] = SegmentPath::normalize($validated['folder']);
        }

        return $mapped;
    }

    protected function savedResponse(Request $request, string $action, mixed $record = null): JsonResponse|RedirectResponse
    {
        if ($this->isPanelRequest($request)) {
            $payload = [
                'message' => $this->flashMessage($action),
            ];

            if ($record !== null) {
                $payload['slug'] = $this->recordRouteParameter($record);
            }

            return response()->json($payload);
        }

        return parent::savedResponse($request, $action, $record);
    }

    protected function isPanelRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->header('X-Segments-Panel') === '1';
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
