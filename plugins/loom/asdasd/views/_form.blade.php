@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];
    $record = $asdasd ?? null;

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
      action="{{ $record ? route('loom.asdasd.update', $record) : route('loom.asdasd.store') }}"
      @foreach ($formAttributes as $attrKey => $attrValue)
          @if (is_bool($attrValue))
              @if ($attrValue) {{ $attrKey }} @endif
          @else
              {{ $attrKey }}="{{ $attrValue }}"
          @endif
      @endforeach>
    @csrf
    @if ($record)
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
        <div class="modal fade loom-block-config-modal" id="block-config-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ collect($modalForms)->first()['meta']['label'] ?? 'Settings' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <button type="button" class="loom-form-btn loom-form-btn--primary" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="loom-form-actions">
        <button type="submit" class="loom-form-btn loom-form-btn--primary">
            {{ $record ? 'Update' : 'Create' }}
        </button>
        <a href="{{ route('loom.asdasd.index') }}" class="loom-form-btn loom-form-btn--secondary">Cancel</a>
        @if ($record)
            <button type="button"
                    data-confirm-form="delete-record-form"
                    data-confirm="Delete this record?"
                    data-confirm-title="Delete record"
                    data-confirm-label="Delete"
                    class="loom-form-btn loom-form-btn--danger ms-auto">Delete</button>
        @endif
    </div>
</form>

@if ($record)
    <form id="delete-record-form" method="POST" action="{{ route('loom.asdasd.destroy', $record) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
