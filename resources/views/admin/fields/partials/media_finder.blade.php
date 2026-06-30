@php
    use Illuminate\Support\Facades\Storage;

    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $class = $class ?? 'form-control';
    $wrapperClass = $wrapperClass ?? 'mb-3';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $required = $required ?? false;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? true;
    $attributes = $attributes ?? [];
    $previewId = $id . '-preview';

    $existingName = null;
    $existingUrl = null;
    $existingIsImage = false;

    if (is_string($value) && $value !== '') {
        $existingName = basename(parse_url($value, PHP_URL_PATH) ?: $value);
        $extension = strtolower(pathinfo($existingName, PATHINFO_EXTENSION));
        $existingIsImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'], true);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $existingUrl = $value;
        } elseif (str_starts_with($value, '/')) {
            $existingUrl = asset(ltrim($value, '/'));
        } elseif (Storage::disk('media')->exists($value)) {
            $existingUrl = Storage::disk('media')->url($value);
        } else {
            $existingUrl = asset('media/' . ltrim($value, '/'));
        }
    }

    $hasExistingPreview = $existingName !== null;
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <div
        class="loom-media-finder-field"
        data-media-finder-field
        @if ($hasExistingPreview)
            data-existing-url="{{ $existingUrl }}"
            data-existing-name="{{ $existingName }}"
            data-existing-is-image="{{ $existingIsImage ? 'true' : 'false' }}"
        @endif
    >
        <div class="input-group">
            <input
                type="text"
                value="{{ $value }}"
                data-media-finder-input
                aria-describedby="{{ $previewId }}"
                @include('admin.fields.partials._attributes')
            >

            @include('admin.partials.action-submit', [
                'icon' => 'bi-folder2-open',
                'label' => 'Find file',
                'variant' => 'input',
                'type' => 'button',
                'extraClass' => 'admin-action-submit--compact',
                'attributes' => array_filter([
                    'data-media-finder-open' => '',
                    'disabled' => $disabled ? 'disabled' : null,
                ]),
            ])

            @include('admin.partials.action-submit', [
                'icon' => 'bi-x-lg',
                'label' => 'Clear',
                'variant' => 'input',
                'type' => 'button',
                'extraClass' => 'admin-action-submit--compact' . ($hasExistingPreview ? '' : ' d-none'),
                'attributes' => array_filter([
                    'data-media-finder-clear' => '',
                    'disabled' => $disabled ? 'disabled' : null,
                ]),
            ])
        </div>

        <div
            id="{{ $previewId }}"
            class="loom-file-preview @if (! $hasExistingPreview) d-none @endif"
            data-media-finder-preview
        >
            <div
                class="loom-file-preview__image @if (! $hasExistingPreview || ! $existingIsImage) d-none @endif"
                data-media-finder-preview-image
            >
                <img
                    src="{{ $existingIsImage ? $existingUrl : '' }}"
                    alt="{{ $existingName ?? 'File preview' }}"
                    data-media-finder-preview-img
                >
            </div>

            <div
                class="loom-file-preview__file @if (! $hasExistingPreview || $existingIsImage) d-none @endif"
                data-media-finder-preview-file
            >
                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                <span data-media-finder-preview-name>{{ $existingName }}</span>
            </div>
        </div>
    </div>
@endcomponent

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
        @vite(['resources/css/assets-file-manager.css'])
    @endpush
@endonce
