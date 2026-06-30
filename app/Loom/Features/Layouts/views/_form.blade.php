@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];

    $regularForms = [];

    foreach ($forms ?? [] as $formKey => $formPanel) {
        if (($formPanel['meta']['layout'] ?? 'panel') !== 'modal') {
            $regularForms[$formKey] = $formPanel;
        }
    }
@endphp

<form @if ($formId) id="{{ $formId }}" @endif
      @if ($formClass) class="{{ $formClass }}" @endif
      method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}"
      @if ($formEnctype) enctype="{{ $formEnctype }}" @endif
      action="{{ isset($layout) ? route('loom.layouts.update', $layout->slug) : route('loom.layouts.store') }}"
      @foreach ($formAttributes as $attrKey => $attrValue)
          @if (is_bool($attrValue))
              @if ($attrValue) {{ $attrKey }} @endif
          @else
              {{ $attrKey }}="{{ $attrValue }}"
          @endif
      @endforeach>
    @csrf
    @if (isset($layout))
        @method('PUT')
    @endif

    @foreach ($regularForms as $formPanel)
        @php
            $layoutRows = $formPanel['layout'] ?? [];
            $nameLayout = [];
            $codeLayout = [];

            foreach ($layoutRows as $row) {
                $fieldNames = collect($row['fields'] ?? [])->map(function ($fieldRef) {
                    return is_string($fieldRef) ? $fieldRef : ($fieldRef['name'] ?? null);
                })->filter()->all();

                if (in_array('code', $fieldNames, true)) {
                    $codeLayout[] = $row;
                } else {
                    $nameLayout[] = $row;
                }
            }
        @endphp

        @if ($nameLayout !== [])
            @include('admin.fields.render-layout', [
                'layout' => $nameLayout,
                'fields' => $formPanel['fields'] ?? [],
                'formScope' => true,
            ])
        @endif

        @if ($codeLayout !== [])
            <div id="layout-form-explorer"
                 class="layout-form-explorer mb-3"
                 data-tree-url="{{ route('loom.segments.tree') }}">
                <div class="layout-form-editor">
                    @include('admin.fields.render-layout', [
                        'layout' => $codeLayout,
                        'fields' => $formPanel['fields'] ?? [],
                        'formScope' => true,
                    ])
                </div>
                <aside class="layout-form-segments-panel" aria-label="Segments">
                    <div class="layout-form-segments-header">Segments</div>
                    <div class="layout-form-segments-scroll">
                        <ul id="layout-segments-tree" class="segments-tree" role="tree"></ul>
                    </div>
                    <p class="layout-form-segments-hint mb-0">Drag a segment into the editor, or click to insert at the cursor.</p>
                </aside>
            </div>
        @endif
    @endforeach

    <div class="loom-form-actions">
        @include('admin.partials.action-submit', [
            'icon' => 'bi-check-lg',
            'label' => isset($layout) ? 'Update layout' : 'Create layout',
            'variant' => 'primary',
            'type' => 'submit',
        ])
        @include('admin.partials.action-link', [
            'href' => route('loom.layouts.index'),
            'icon' => 'bi-x-lg',
            'label' => 'Cancel',
            'variant' => 'muted',
        ])
        @if (isset($layout))
            @include('admin.partials.action-submit', [
                'icon' => 'bi-trash',
                'label' => 'Delete',
                'variant' => 'danger',
                'type' => 'button',
                'extraClass' => 'ms-auto',
                'attributes' => [
                    'data-confirm-form' => 'delete-layout-form',
                    'data-confirm' => 'Delete this layout?',
                    'data-confirm-title' => 'Delete layout',
                    'data-confirm-label' => 'Delete',
                ],
            ])
        @endif
    </div>
</form>

@if (isset($layout))
    <form id="delete-layout-form"
          method="POST"
          action="{{ route('loom.layouts.destroy', $layout->slug) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
