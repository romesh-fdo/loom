@php
    $name = $name ?? '';
    $label = $label ?? null;
    $type = $type ?? 'media_selector';
    $value = $value ?? old($name, $value ?? '');
    $wrapperClass = $wrapperClass ?? 'col-12';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $disabled = $disabled ?? false;

    $compound = \Loom\Support\MediaParameterProcessor::normalizeCompoundValue($value);
    $id = $id ?? 'media-param-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $hasUrl = $compound['url'] !== '';
    $existingName = $hasUrl ? basename(parse_url($compound['url'], PHP_URL_PATH) ?: $compound['url']) : '';
    $existingIsImage = $existingName !== '' && preg_match('/\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i', $existingName);
    $mode = $type === 'media_attach' ? 'attach' : 'selector';
@endphp

<div class="{{ $wrapperClass }}">
    <div class="loom-media-parameter-field"
         data-media-parameter-field
         data-media-mode="{{ $mode }}"
         @if ($disabled) data-disabled="true" @endif>
        <label class="{{ $labelClass }}">{{ $label }}</label>

        <input type="hidden" name="{{ $name }}[url]" value="{{ $compound['url'] }}" data-media-param-url>
        <input type="hidden" name="{{ $name }}[alt]" value="{{ $compound['alt'] }}" data-media-param-alt>
        <input type="hidden" name="{{ $name }}[class]" value="{{ $compound['class'] }}" data-media-param-class>

        <div class="loom-media-parameter-trigger-wrap">
            <button type="button"
                    class="loom-media-parameter-trigger @if ($hasUrl) d-none @endif"
                    data-media-parameter-open
                    data-media-parameter-empty
                    @if ($disabled) disabled @endif>
                <span class="loom-media-parameter-trigger__placeholder">
                    <i class="bi bi-image" aria-hidden="true"></i>
                    Choose media
                </span>
            </button>

            <div class="loom-media-parameter-display @if (! $hasUrl) d-none @endif"
                 data-media-parameter-display>
                <div class="loom-media-parameter-display__visual">
                    <span class="loom-media-parameter-display__image @if (! $hasUrl || ! $existingIsImage) d-none @endif"
                          data-media-parameter-preview-image>
                        <img src="{{ $existingIsImage ? $compound['url'] : '' }}"
                             alt="{{ $existingName ?: 'Preview' }}"
                             data-media-parameter-preview-img>
                    </span>
                    <span class="loom-media-parameter-display__file @if (! $hasUrl || $existingIsImage) d-none @endif"
                          data-media-parameter-preview-file>
                        <i class="bi bi-file-earmark" aria-hidden="true"></i>
                        <span data-media-parameter-preview-name>{{ $existingName }}</span>
                    </span>
                </div>
                <div class="loom-media-parameter-display__actions">
                    @if ($hasUrl)
                        <a class="loom-media-parameter-display__preview-link"
                           href="{{ $compound['url'] }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-media-parameter-preview-link>
                            Preview
                        </a>
                    @else
                        <a class="loom-media-parameter-display__preview-link d-none"
                           href="#"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-media-parameter-preview-link>
                            Preview
                        </a>
                    @endif
                    @if (! $disabled)
                        <button type="button"
                                class="loom-media-parameter-display__change"
                                data-media-parameter-open>
                            Change
                        </button>
                    @endif
                </div>
                @if (! $disabled)
                    <button type="button"
                            class="loom-media-parameter-clear"
                            data-media-parameter-clear
                            aria-label="Clear media">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
        @vite(['resources/css/assets-file-manager.css'])
    @endpush
@endonce
