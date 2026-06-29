@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];
@endphp

<form @if ($formId) id="{{ $formId }}" @endif
      @if ($formClass) class="{{ $formClass }}" @endif
      method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}"
      @if ($formEnctype) enctype="{{ $formEnctype }}" @endif
      action="{{ isset($page) ? route('loom.pages.update', $page->slug) : route('loom.pages.store') }}"
      @foreach ($formAttributes as $attrKey => $attrValue)
          @if (is_bool($attrValue))
              @if ($attrValue) {{ $attrKey }} @endif
          @else
              {{ $attrKey }}="{{ $attrValue }}"
          @endif
      @endforeach>
    @csrf
    @if (isset($page))
        @method('PUT')
    @endif

    @foreach ($forms ?? [] as $formPanel)
        @include('admin.fields.render-layout', [
            'layout' => $formPanel['layout'] ?? [],
            'fields' => $formPanel['fields'] ?? [],
            'formScope' => true,
            'blocksCatalog' => $blocksCatalog ?? [],
        ])
    @endforeach

    <div class="loom-form-actions mt-4">
        <button type="submit" class="loom-form-btn loom-form-btn--primary">
            {{ isset($page) ? 'Update page' : 'Create page' }}
        </button>
        <a href="{{ route('loom.pages.index') }}" class="loom-form-btn loom-form-btn--secondary">Cancel</a>
        @if (isset($page))
            <button type="button"
                    data-confirm-form="delete-page-form"
                    data-confirm="Delete this page?"
                    data-confirm-title="Delete page"
                    data-confirm-label="Delete"
                    class="loom-form-btn loom-form-btn--danger ms-auto">
                Delete
            </button>
        @endif
    </div>
</form>

@if (isset($page))
    <form id="delete-page-form"
          method="POST"
          action="{{ route('loom.pages.destroy', $page->slug) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
