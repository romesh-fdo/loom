@php
    use Loom\Support\UrlParameterProcessor;

    $name = $name ?? '';
    $label = $label ?? null;
    $value = $value ?? old($name, $value ?? '');
    $wrapperClass = $wrapperClass ?? 'col-12';
    $labelClass = $labelClass ?? 'form-label';
    $help = $help ?? null;
    $disabled = $disabled ?? false;

    $compound = UrlParameterProcessor::normalizeCompoundValue($value);
    $hasUrl = $compound['url'] !== '';
    $displayUrl = $hasUrl ? $compound['url'] : '';
@endphp

<div class="{{ $wrapperClass }}">
    <div class="loom-url-parameter-field"
         data-url-parameter-field
         @if ($disabled) data-disabled="true" @endif>
        <label class="{{ $labelClass }}">{{ $label }}</label>

        <input type="hidden" name="{{ $name }}[url]" value="{{ $compound['url'] }}" data-url-param-url>
        <input type="hidden" name="{{ $name }}[class]" value="{{ $compound['class'] }}" data-url-param-class>
        <input type="hidden" name="{{ $name }}[id]" value="{{ $compound['id'] }}" data-url-param-id>
        <input type="hidden" name="{{ $name }}[target]" value="{{ $compound['target'] }}" data-url-param-target>

        <div class="loom-media-parameter-trigger-wrap">
            <button type="button"
                    class="loom-media-parameter-trigger"
                    data-url-parameter-open
                    @if ($disabled) disabled @endif>
                <span class="loom-media-parameter-trigger__placeholder @if ($hasUrl) d-none @endif"
                      data-url-parameter-placeholder>
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    Set link
                </span>
                <span class="loom-media-parameter-trigger__preview @if (! $hasUrl) d-none @endif"
                      data-url-parameter-preview>
                    <span class="loom-media-parameter-trigger__preview-file">
                        <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    </span>
                    <span class="loom-media-parameter-trigger__filename" data-url-parameter-preview-label>
                        {{ $displayUrl }}
                    </span>
                </span>
            </button>

            @if (! $disabled)
                <button type="button"
                        class="loom-media-parameter-clear @if (! $hasUrl) d-none @endif"
                        data-url-parameter-clear
                        aria-label="Clear link">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </div>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
