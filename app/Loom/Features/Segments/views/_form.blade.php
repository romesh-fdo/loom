@php
    $formId = $form['id'] ?? null;
    $formClass = $form['class'] ?? null;
    $formMethod = strtoupper($form['method'] ?? 'POST');
    $formEnctype = $form['enctype'] ?? 'multipart/form-data';
    $formAttributes = $form['attributes'] ?? [];
    $segmentParameters = $segmentParameters ?? [];
    $segmentValues = $segmentValues ?? [];
@endphp

<form @if ($formId) id="{{ $formId }}" @endif
      @if ($formClass) class="{{ $formClass }}" @endif
      method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}"
      @if ($formEnctype) enctype="{{ $formEnctype }}" @endif
      action="{{ isset($segment) ? route('loom.segments.update', $segment->slug) : route('loom.segments.store') }}"
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
    @endif

    @foreach ($forms ?? [] as $formPanel)
        @include('admin.fields.render-layout', [
            'layout' => $formPanel['layout'] ?? [],
            'fields' => $formPanel['fields'] ?? [],
            'formScope' => true,
        ])
    @endforeach

    @if (count($segmentParameters) > 0)
        <div class="loom-form-row row g-3 mb-3">
            <div class="col-12">
                <h6 class="form-label mb-2">Parameter values</h6>
                <p class="text-muted small">Theme-wide values for this segment's dynamic parameters.</p>
            </div>
            @foreach ($segmentParameters as $parameter)
                @php
                    $paramName = $parameter['name'] ?? '';
                    $paramType = $parameter['type'] ?? 'text';
                    $paramLabel = $parameter['label'] ?? $paramName;
                    $paramTip = trim((string) ($parameter['tip'] ?? ''));
                    $value = old("values.{$paramName}", $segmentValues[$paramName] ?? ($parameter['default'] ?? ''));
                    $fieldName = "values[{$paramName}]";
                @endphp
                @if ($paramType === 'textarea')
                    <div class="col-12">
                        <label class="form-label" for="segment-value-{{ $paramName }}">{{ $paramLabel }}</label>
                        <textarea class="form-control" id="segment-value-{{ $paramName }}" name="{{ $fieldName }}" rows="3">{{ $value }}</textarea>
                        @if ($paramTip !== '')
                            <div class="form-text">{{ $paramTip }}</div>
                        @endif
                    </div>
                @elseif ($paramType === 'checkbox')
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="hidden" name="{{ $fieldName }}" value="0">
                            <input type="checkbox"
                                   class="form-check-input"
                                   id="segment-value-{{ $paramName }}"
                                   name="{{ $fieldName }}"
                                   value="1"
                                   @checked($value === true || $value === '1' || $value === 1)>
                            <label class="form-check-label" for="segment-value-{{ $paramName }}">{{ $paramLabel }}</label>
                        </div>
                        @if ($paramTip !== '')
                            <div class="form-text">{{ $paramTip }}</div>
                        @endif
                    </div>
                @elseif ($paramType === 'select')
                    <div class="col-md-6">
                        <label class="form-label" for="segment-value-{{ $paramName }}">{{ $paramLabel }}</label>
                        <select class="form-select" id="segment-value-{{ $paramName }}" name="{{ $fieldName }}">
                            <option value="" disabled @selected($value === '' || $value === null)>Select…</option>
                            @foreach ($parameter['options'] ?? [] as $option)
                                @php
                                    $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option;
                                    $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                                @endphp
                                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        @if ($paramTip !== '')
                            <div class="form-text">{{ $paramTip }}</div>
                        @endif
                    </div>
                @else
                    <div class="col-md-6">
                        <label class="form-label" for="segment-value-{{ $paramName }}">{{ $paramLabel }}</label>
                        <input type="{{ in_array($paramType, ['number', 'email', 'color'], true) ? $paramType : 'text' }}"
                               class="form-control"
                               id="segment-value-{{ $paramName }}"
                               name="{{ $fieldName }}"
                               value="{{ $value }}">
                        @if ($paramTip !== '')
                            <div class="form-text">{{ $paramTip }}</div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="loom-form-actions">
        <button type="submit" class="loom-form-btn loom-form-btn--primary">
            {{ isset($segment) ? 'Update segment' : 'Create segment' }}
        </button>
        <a href="{{ route('loom.segments.index') }}" class="loom-form-btn loom-form-btn--secondary">Cancel</a>
        @if (isset($segment))
            <button type="button"
                    data-confirm-form="delete-segment-form"
                    data-confirm="Delete this segment?"
                    data-confirm-title="Delete segment"
                    data-confirm-label="Delete"
                    class="loom-form-btn loom-form-btn--danger ms-auto">
                Delete
            </button>
        @endif
    </div>
</form>

@if (isset($segment))
    <form id="delete-segment-form"
          method="POST"
          action="{{ route('loom.segments.destroy', $segment->slug) }}"
          class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endif
