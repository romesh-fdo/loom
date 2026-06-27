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
    $readonly = $readonly ?? false;
    $attributes = $attributes ?? [];
    $accept = $accept ?? null;
    $previewId = $id . '-preview';

    if ($accept) {
        $attributes['accept'] = $accept;
    }

    $existingName = null;
    $existingUrl = null;
    $existingIsImage = false;

    if (is_string($value) && $value !== '') {
        $existingName = basename($value);
        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        $existingIsImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'], true);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $existingUrl = $value;
        } elseif (str_starts_with($value, '/')) {
            $existingUrl = asset(ltrim($value, '/'));
        } elseif (Storage::disk('public')->exists($value)) {
            $existingUrl = Storage::disk('public')->url($value);
        } else {
            $existingUrl = asset('storage/' . ltrim($value, '/'));
        }
    }

    $hasExistingPreview = $existingName !== null;
@endphp

@component('admin.fields.partials._wrapper', compact(
    'name', 'label', 'id', 'wrapperClass', 'labelClass', 'required', 'help'
))
    <div
        class="loom-file-field"
        data-file-field
        @if ($hasExistingPreview)
            data-existing-url="{{ $existingUrl }}"
            data-existing-name="{{ $existingName }}"
            data-existing-is-image="{{ $existingIsImage ? 'true' : 'false' }}"
        @endif
    >
        <input
            type="file"
            data-file-input
            aria-describedby="{{ $previewId }}"
            @include('admin.fields.partials._attributes')
        >

        <div
            id="{{ $previewId }}"
            class="loom-file-preview @if (! $hasExistingPreview) d-none @endif"
            data-file-preview
        >
            <div
                class="loom-file-preview__image @if (! $hasExistingPreview || ! $existingIsImage) d-none @endif"
                data-file-preview-image
            >
                <img
                    src="{{ $existingIsImage ? $existingUrl : '' }}"
                    alt="{{ $existingName ?? 'File preview' }}"
                    data-file-preview-img
                >
            </div>

            <div
                class="loom-file-preview__file @if (! $hasExistingPreview || $existingIsImage) d-none @endif"
                data-file-preview-file
            >
                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                <span data-file-preview-name>{{ $existingName }}</span>
            </div>
        </div>
    </div>
@endcomponent

@once
    <script>
        (function () {
            var imagePattern = /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i;

            function isImageFile(file) {
                if (file.type && file.type.indexOf('image/') === 0) {
                    return true;
                }

                return imagePattern.test(file.name);
            }

            function show(el) {
                el.classList.remove('d-none');
            }

            function hide(el) {
                el.classList.add('d-none');
            }

            function initFileField(field) {
                if (field.dataset.filePreviewInit === 'true') {
                    return;
                }

                var input = field.querySelector('[data-file-input]');
                var preview = field.querySelector('[data-file-preview]');
                var imageWrap = field.querySelector('[data-file-preview-image]');
                var fileWrap = field.querySelector('[data-file-preview-file]');
                var image = field.querySelector('[data-file-preview-img]');
                var fileName = field.querySelector('[data-file-preview-name]');

                if (!input || !preview || !imageWrap || !fileWrap || !image || !fileName) {
                    return;
                }

                field.dataset.filePreviewInit = 'true';

                var objectUrl = null;
                var existingUrl = field.dataset.existingUrl || '';
                var existingName = field.dataset.existingName || '';
                var existingIsImage = field.dataset.existingIsImage === 'true';

                function revokeObjectUrl() {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                        objectUrl = null;
                    }
                }

                function showImage(src, alt) {
                    hide(fileWrap);
                    show(imageWrap);
                    image.src = src;
                    image.alt = alt;
                    show(preview);
                }

                function showFileLabel(name) {
                    hide(imageWrap);
                    image.removeAttribute('src');
                    show(fileWrap);
                    fileName.textContent = name;
                    show(preview);
                }

                function showExistingPreview() {
                    if (!existingName) {
                        return false;
                    }

                    if (existingIsImage && existingUrl) {
                        showImage(existingUrl, existingName);
                    } else {
                        showFileLabel(existingName);
                    }

                    return true;
                }

                function clearPreview() {
                    revokeObjectUrl();
                    image.removeAttribute('src');
                    image.alt = 'File preview';
                    fileName.textContent = '';
                    hide(imageWrap);
                    hide(fileWrap);
                    hide(preview);
                }

                input.addEventListener('change', function () {
                    revokeObjectUrl();

                    var file = input.files && input.files[0];
                    if (!file) {
                        if (!showExistingPreview()) {
                            clearPreview();
                        }
                        return;
                    }

                    if (isImageFile(file)) {
                        objectUrl = URL.createObjectURL(file);
                        showImage(objectUrl, file.name);
                        return;
                    }

                    showFileLabel(file.name);
                });
            }

            function initAllFileFields() {
                document.querySelectorAll('[data-file-field]').forEach(initFileField);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAllFileFields);
            } else {
                initAllFileFields();
            }
        })();
    </script>
@endonce
