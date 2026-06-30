@php
    $selectedIcon = old('plugin_icon', $selectedIcon ?? 'bi-box');
    $iconsUrl = route('loom.plugin-builder.icons');
@endphp

<div class="plugin-builder-icon-picker" data-plugin-builder-icon-picker data-icons-url="{{ $iconsUrl }}">
    <label class="form-label" for="plugin_icon_trigger">Icon</label>
    <input type="hidden" name="plugin_icon" id="plugin_icon" value="{{ $selectedIcon }}">
    <div class="dropdown w-100">
        <button type="button"
                class="admin-action-submit admin-action-submit--secondary admin-action-submit--stretch w-100 plugin-builder-icon-picker__trigger text-start text-truncate"
                id="plugin_icon_trigger"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false">
            <i class="bi {{ $selectedIcon }}" data-plugin-builder-icon-preview aria-hidden="true"></i>
            <span data-plugin-builder-icon-label>{{ str_replace('bi-', '', $selectedIcon) }}</span>
        </button>
        <div class="dropdown-menu w-100 p-2 plugin-builder-icon-picker__menu">
            <input type="search"
                   class="form-control mb-2"
                   placeholder="Search icons…"
                   data-plugin-builder-icon-search
                   autocomplete="off">
            <div class="plugin-builder-icon-picker__list" data-plugin-builder-icon-list role="listbox"></div>
        </div>
    </div>
</div>
