@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];

    $modalForms = [];
    $regularForms = [];

    foreach ($forms ?? [] as $formKey => $formPanel) {
        if (($formPanel['meta']['layout'] ?? 'panel') === 'modal') {
            $modalForms[$formKey] = $formPanel;
        } else {
            $regularForms[$formKey] = $formPanel;
        }
    }
@endphp

<form @if ($formId) id="{{ $formId }}" @endif
      @if ($formClass) class="{{ $formClass }}" @endif
      method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}"
      @if ($formEnctype) enctype="{{ $formEnctype }}" @endif
      action="{{ isset($block) ? route('loom.blocks.update', $block) : route('loom.blocks.store') }}"
      @foreach ($formAttributes as $attrKey => $attrValue)
          @if (is_bool($attrValue))
              @if ($attrValue) {{ $attrKey }} @endif
          @else
              {{ $attrKey }}="{{ $attrValue }}"
          @endif
      @endforeach>
    @csrf
    @if (isset($block))
        @method('PUT')
    @endif

    @foreach ($regularForms as $formPanel)
        @include('admin.fields.render-layout', [
            'layout' => $formPanel['layout'] ?? [],
            'fields' => $formPanel['fields'] ?? [],
            'formScope' => true,
        ])
    @endforeach

    @if (!empty($modalForms))
        <div class="modal fade loom-block-config-modal"
             id="block-config-modal"
             tabindex="-1"
             aria-labelledby="block-config-modal-label"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="block-config-modal-label">
                                {{ collect($modalForms)->first()['meta']['label'] ?? 'Block settings' }}
                            </h5>
                            @php $modalDescription = collect($modalForms)->first()['meta']['description'] ?? null; @endphp
                            @if ($modalDescription)
                                <p class="loom-block-config-modal__description">{{ $modalDescription }}</p>
                            @endif
                        </div>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @foreach ($modalForms as $formPanel)
                            @include('admin.fields.render-layout', [
                                'layout' => $formPanel['layout'] ?? [],
                                'fields' => $formPanel['fields'] ?? [],
                                'formScope' => true,
                            ])
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="loom-form-btn loom-form-btn--primary"
                                data-bs-dismiss="modal">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="loom-form-actions">
        <button type="submit" class="loom-form-btn loom-form-btn--primary">
            {{ isset($block) ? 'Update block' : 'Create block' }}
        </button>
        <a href="{{ route('loom.blocks.index') }}" class="loom-form-btn loom-form-btn--secondary">Cancel</a>
        @if (isset($block))
            <button type="submit"
                    form="delete-block-form"
                    class="loom-form-btn loom-form-btn--danger ms-auto"
                    onclick="return confirm('Delete this block?')">
                Delete
            </button>
        @endif
    </div>
</form>

@if (isset($block))
    <form id="delete-block-form"
          method="POST"
          action="{{ route('loom.blocks.destroy', $block) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
