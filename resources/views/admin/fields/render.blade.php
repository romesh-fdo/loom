@php
    $allowedTypes = [
        'text', 'email', 'url', 'password', 'number', 'textarea', 'select',
        'checkbox', 'radio', 'date', 'datetime-local', 'file', 'media_finder', 'hidden', 'code', 'dynamic_code', 'color', 'repeater', 'richtext',
    ];
@endphp

@foreach ($fields as $name => $field)
    @php
        $type = $field['type'] ?? $field['input'] ?? 'text';
        $fieldConfig = array_merge($field, ['name' => $name]);
    @endphp

    @if (in_array($type, $allowedTypes, true))
        @includeIf("admin.fields.partials.{$type}", $fieldConfig)
    @else
        <div class="alert alert-warning mb-3" role="alert">
            Unknown field type <strong>{{ $type }}</strong> for field <strong>{{ $name }}</strong>.
        </div>
    @endif
@endforeach
