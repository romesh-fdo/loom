<?php

namespace Loom\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsToAjaxFormSave
{
    protected function editRoute(): string
    {
        return str_replace('.index', '.edit', $this->indexRoute());
    }

    /**
     * @param  mixed  $record  Route parameter for edit URL after create.
     */
    protected function savedResponse(Request $request, string $action, mixed $record = null): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            $payload = [
                'message' => $this->flashMessage($action),
            ];

            if ($action === 'created' && $record !== null) {
                $payload['redirect'] = route($this->editRoute(), $this->recordRouteParameter($record));
            }

            return response()->json($payload);
        }

        return redirect()
            ->route($this->indexRoute())
            ->with('success', $this->flashMessage($action));
    }

    /**
     * @param  mixed  $record
     */
    protected function recordRouteParameter(mixed $record): mixed
    {
        if (is_object($record) && property_exists($record, 'slug')) {
            return $record->slug;
        }

        if (is_object($record) && method_exists($record, 'getRouteKey')) {
            return $record->getRouteKey();
        }

        return $record;
    }
}
