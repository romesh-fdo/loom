@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];
    $panelMode = $panelMode ?? false;
    $folder = $folder ?? '';
    $parentFolder = isset($segment)
        ? \Loom\Support\ThemeContent\SegmentPath::dirname($segment->slug)
        : $folder;
    $parentFolderPath = 'segments'.($parentFolder !== '' ? ' / '.str_replace('/', ' / ', $parentFolder) : '');
    $isCreate = ! isset($segment);
@endphp

@if ($panelMode)
    <div class="segments-parent-folder" aria-label="Parent folder">
        <i class="bi bi-folder2 segments-parent-folder-icon" aria-hidden="true"></i>
        <div class="segments-parent-folder-text">
            <span class="segments-parent-folder-label">Location</span>
            <span class="segments-parent-folder-path">{{ $parentFolderPath }}</span>
        </div>
    </div>
@endif

<form @if ($formId) id="{{ $formId }}" @endif
      @if ($formClass) class="{{ $formClass }}" @endif
      method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}"
      @if ($formEnctype) enctype="{{ $formEnctype }}" @endif
      action="{{ isset($segment) ? route('loom.segments.update', $segment->slug) : route('loom.segments.store') }}"
      @if ($panelMode) data-segments-panel-form @endif
      @foreach ($formAttributes as $attrKey => $attrValue)
          @if (is_bool($attrValue))
              @if ($attrValue) {{ $attrKey }} @endif
          @else
              {{ $attrKey }}="{{ $attrValue }}"
          @endif
      @endforeach>
    @csrf
    @if (isset($segment))
        @method('PUT')
    @elseif ($panelMode)
        <input type="hidden" name="folder" value="{{ $folder }}">
    @endif

    @foreach ($forms ?? [] as $formPanel)
        @include('admin.fields.render-layout', [
            'layout' => $formPanel['layout'] ?? [],
            'fields' => $formPanel['fields'] ?? [],
            'fieldValues' => $segmentCreateDefaults ?? [],
            'formScope' => true,
        ])
    @endforeach

    <div @class(['loom-form-actions', 'loom-form-actions--after-editor' => $panelMode])>
        @include('admin.partials.action-submit', [
            'icon' => 'bi-check-lg',
            'label' => $panelMode && $isCreate ? 'Save' : (isset($segment) ? 'Update segment' : 'Create segment'),
            'variant' => 'primary',
            'type' => 'submit',
        ])
        @if ($panelMode)
            @include('admin.partials.action-link', [
                'href' => '#',
                'icon' => 'bi-x-lg',
                'label' => 'Cancel',
                'variant' => 'muted',
                'attributes' => ['data-segments-cancel' => ''],
            ])
        @else
            @include('admin.partials.action-link', [
                'href' => route('loom.segments.index'),
                'icon' => 'bi-x-lg',
                'label' => 'Cancel',
                'variant' => 'muted',
            ])
        @endif
        @if (isset($segment))
            @include('admin.partials.action-submit', [
                'icon' => 'bi-trash',
                'label' => 'Delete',
                'variant' => 'danger',
                'type' => 'button',
                'extraClass' => 'ms-auto',
                'attributes' => $panelMode ? [
                    'data-segments-delete' => 'delete-segment-form',
                    'data-confirm' => 'Delete this segment?',
                    'data-confirm-title' => 'Delete segment',
                    'data-confirm-label' => 'Delete',
                ] : [
                    'data-confirm-form' => 'delete-segment-form',
                    'data-confirm' => 'Delete this segment?',
                    'data-confirm-title' => 'Delete segment',
                    'data-confirm-label' => 'Delete',
                ],
            ])
        @endif
    </div>
</form>

@if (isset($segment))
    <form id="delete-segment-form"
          method="POST"
          action="{{ ($panelMode ?? false) ? route('loom.segments.panel.destroy', $segment->slug) : route('loom.segments.destroy', $segment->slug) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
