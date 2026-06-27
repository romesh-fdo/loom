@php
    $selectedIcon = old('plugin_icon', $selectedIcon ?? 'bi-box');
    $iconsUrl = route('loom.plugin-builder.icons');
@endphp

<div class="plugin-builder-icon-picker" data-plugin-builder-icon-picker data-icons-url="{{ $iconsUrl }}">
    <label class="form-label" for="plugin_icon_trigger">Icon</label>
    <input type="hidden" name="plugin_icon" id="plugin_icon" value="{{ $selectedIcon }}">
    <div class="dropdown w-100">
        <button type="button"
                class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2 text-start plugin-builder-icon-picker__trigger"
                id="plugin_icon_trigger"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false">
            <i class="bi {{ $selectedIcon }}" data-plugin-builder-icon-preview></i>
            <span class="text-truncate" data-plugin-builder-icon-label>{{ str_replace('bi-', '', $selectedIcon) }}</span>
        </button>
        <div class="dropdown-menu w-100 p-2 plugin-builder-icon-picker__menu">
            <input type="search"
                   class="form-control form-control-sm mb-2"
                   placeholder="Search icons…"
                   data-plugin-builder-icon-search
                   autocomplete="off">
            <div class="plugin-builder-icon-picker__list" data-plugin-builder-icon-list role="listbox"></div>
        </div>
    </div>
</div>
