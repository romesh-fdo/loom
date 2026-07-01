@php
    $name = $name ?? 'layout_fields';
    $errorKey = $errorKey ?? 'layout_fields';
    $label = $label ?? 'Layout fields';
    $value = $value ?? [];
    $value = is_array($value) ? $value : [];
    $id = $id ?? 'field-layout-fields';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? 'Configure values for layout segments on this page (e.g. SEO meta tags).';
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $layoutsCatalog = $layoutsCatalog ?? [];
    $selectedLayout = old('layout', $selectedLayout ?? '');
    $entityImports = is_array($entityImports ?? null) ? array_values($entityImports) : [];
    $pluginsFunctionsCatalog = is_array($pluginsFunctionsCatalog ?? null) ? $pluginsFunctionsCatalog : [];
    $accordionId = $id.'-accordion';
    $layoutFieldErrors = $errors->get($errorKey) ?? [];

    $oldValue = old($name);
    if (is_array($oldValue)) {
        $value = $oldValue;
    }

    $activeLayout = collect($layoutsCatalog)->first(
        fn (array $layout) => ($layout['slug'] ?? '') === $selectedLayout
    );
    $segments = is_array($activeLayout['segments'] ?? null) ? $activeLayout['segments'] : [];
    $hasLayoutFieldErrors = $layoutFieldErrors !== [];
@endphp

<div class="loom-layout-fields-accordion {{ $wrapperClass }}">
    <div class="accordion" id="{{ $accordionId }}">
        <div class="accordion-item @if ($hasLayoutFieldErrors) border-danger @endif">
            <h2 class="accordion-header">
                <button class="accordion-button {{ ($segments === [] && ! $hasLayoutFieldErrors) ? 'collapsed' : '' }}"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $accordionId }}-panel"
                        aria-expanded="{{ ($segments !== [] || $hasLayoutFieldErrors) ? 'true' : 'false' }}"
                        aria-controls="{{ $accordionId }}-panel">
                    {{ $label }}
                    @if ($hasLayoutFieldErrors)
                        <span class="badge text-bg-danger ms-2">{{ count($layoutFieldErrors) }}</span>
                    @endif
                </button>
            </h2>
            <div id="{{ $accordionId }}-panel"
                 class="accordion-collapse collapse {{ ($segments !== [] || $hasLayoutFieldErrors) ? 'show' : '' }}"
                 data-bs-parent="#{{ $accordionId }}">
                <div class="accordion-body">
                    @if ($help)
                        <p class="text-muted small mb-3">{{ $help }}</p>
                    @endif

                    <div class="loom-page-layout-fields"
                         id="{{ $id }}"
                         data-page-layout-fields
                         data-name="{{ $name }}"
                         @if ($disabled) data-disabled="true" @endif>
                        <script type="application/json" data-layouts-catalog>@json($layoutsCatalog)</script>
                        <script type="application/json" data-layout-fields-values>@json($value)</script>
                        <script type="application/json" data-entity-imports-for-layout>@json($entityImports)</script>
                        <script type="application/json" data-plugins-functions-catalog>@json($pluginsFunctionsCatalog)</script>

                        <div data-page-layout-fields-content>
                            @if ($hasLayoutFieldErrors)
                                <div class="alert alert-danger py-2 small mb-3" role="alert" data-layout-fields-errors>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($layoutFieldErrors as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($selectedLayout === '')
                                <p class="text-muted small mb-0">Select a layout to configure segment fields.</p>
                            @elseif ($segments === [])
                                <p class="text-muted small mb-0">This layout has no configurable segment fields.</p>
                            @else
                                @php
                                    $tabsId = $id.'-segment-tabs';
                                @endphp
                                <ul class="nav nav-tabs loom-layout-fields-tabs" id="{{ $tabsId }}" role="tablist">
                                    @foreach ($segments as $segmentIndex => $segment)
                                        @php
                                            $segmentPath = (string) ($segment['path'] ?? '');
                                            $segmentName = (string) ($segment['name'] ?? $segmentPath);
                                            $tabId = $id.'-tab-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $segmentPath);
                                            $paneId = $id.'-pane-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $segmentPath);
                                        @endphp
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link {{ $segmentIndex === 0 ? 'active' : '' }}"
                                                    id="{{ $tabId }}"
                                                    data-bs-toggle="tab"
                                                    data-bs-target="#{{ $paneId }}"
                                                    type="button"
                                                    role="tab"
                                                    aria-controls="{{ $paneId }}"
                                                    aria-selected="{{ $segmentIndex === 0 ? 'true' : 'false' }}">
                                                {{ $segmentName }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content loom-layout-fields-tab-content border border-top-0 rounded-bottom p-3">
                                    @foreach ($segments as $segmentIndex => $segment)
                                        @php
                                            $segmentPath = (string) ($segment['path'] ?? '');
                                            $parameters = is_array($segment['parameters'] ?? null) ? $segment['parameters'] : [];
                                            $segmentValues = is_array($value[$segmentPath] ?? null) ? $value[$segmentPath] : [];
                                            $segmentBase = "{$name}[{$segmentPath}]";
                                            $paneId = $id.'-pane-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $segmentPath);
                                            $tabId = $id.'-tab-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $segmentPath);
                                        @endphp
                                        <div class="tab-pane fade {{ $segmentIndex === 0 ? 'show active' : '' }}"
                                             id="{{ $paneId }}"
                                             role="tabpanel"
                                             aria-labelledby="{{ $tabId }}"
                                             data-layout-segment="{{ $segmentPath }}">
                                            @include('admin.fields.partials._layout-segment-fields', [
                                                'baseName' => $segmentBase,
                                                'segmentPath' => $segmentPath,
                                                'parameters' => $parameters,
                                                'values' => $segmentValues,
                                                'disabled' => $disabled,
                                                'entityImports' => $entityImports,
                                                'pluginsFunctionsCatalog' => $pluginsFunctionsCatalog,
                                                'layoutFieldErrors' => $layoutFieldErrors,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
