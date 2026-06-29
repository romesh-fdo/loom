@php
    $plugin = $definition['plugin'] ?? [];
    $model = $definition['model'] ?? [];
    $isNew = (bool) ($definition['is_new'] ?? false);
    $pluginSlug = $pluginSlug ?? null;

    $fields = [];
    foreach ($definition['forms'] ?? [] as $form) {
        if (($form['key'] ?? '') !== 'basic-form') {
            continue;
        }

        foreach ($form['fields'] ?? [] as $field) {
            $fields[] = $field;
        }
    }

    $fieldTypes = $fieldTypes ?? \Loom\Builder\FieldTypeRegistry::labels();
    $tablePrefix = $tablePrefix ?? \Loom\Builder\TableNames::prefix();
    $tableSuffix = \Loom\Builder\TableNames::stripPrefix(old('table_name', $model['table'] ?? ''));
    $selectedIcon = old('plugin_icon', $plugin['icon'] ?? 'bi-box');
@endphp

<form method="POST"
      action="{{ $pluginSlug ? route('loom.plugin-builder.update', $pluginSlug) : route('loom.plugin-builder.store') }}"
      class="loom-plugin-builder-form"
      data-plugin-builder-form>
    @csrf
    @if ($pluginSlug)
        @method('PUT')
    @endif

    <div class="row g-3 mb-4">
        @if ($isNew && ! $pluginSlug)
            <div class="col-md-6">
                <label class="form-label" for="plugin_label">Label</label>
                <input type="text" name="plugin_label" id="plugin_label" class="form-control"
                       value="{{ old('plugin_label', $plugin['label'] ?? '') }}" required
                       data-plugin-builder-plugin-label>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="plugin_slug">Plugin slug</label>
                <input type="text" name="plugin_slug" id="plugin_slug" class="form-control font-monospace"
                       value="{{ old('plugin_slug', $plugin['name'] ?? '') }}"
                       pattern="[a-z][a-z0-9-]*" required placeholder="my-items"
                       data-plugin-builder-plugin-slug>
                <div class="form-text">Folder name under plugins/loom/</div>
            </div>
        @else
            <div class="col-md-6">
                <label class="form-label" for="plugin_label">Label</label>
                <input type="text" name="plugin_label" id="plugin_label" class="form-control"
                       value="{{ old('plugin_label', $plugin['label'] ?? '') }}" required>
            </div>
        @endif
        <div class="col-md-4">
            <label class="form-label" for="route_slug">Route slug</label>
            <input type="text" name="route_slug" id="route_slug" class="form-control"
                   value="{{ old('route_slug', $plugin['route'] ?? '') }}"
                   pattern="[a-z][a-z0-9-]*" required placeholder="my-items">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="model_class">Model class</label>
            <input type="text" name="model_class" id="model_class" class="form-control"
                   value="{{ old('model_class', $model['class'] ?? '') }}" required placeholder="MyItem">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="table_name">Table name</label>
            <div class="input-group">
                <span class="input-group-text font-monospace text-muted">{{ $tablePrefix }}</span>
                <input type="text" name="table_name" id="table_name" class="form-control font-monospace"
                       value="{{ $tableSuffix }}"
                       pattern="[a-z][a-z0-9_]*" required placeholder="my_items"
                       data-table-prefix="{{ $tablePrefix }}">
            </div>
            <div class="form-text">Prefix is always applied (e.g. {{ $tablePrefix }}my_items).</div>
        </div>
        <div class="col-md-4">
            @include('loom-plugin-builder::_icon-picker', ['selectedIcon' => $selectedIcon])
        </div>
    </div>

    <div class="plugin-builder-fields-section mb-4">
        <div class="plugin-builder-fields-section__header">
            <h3 class="h6 mb-0">Fields</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" data-plugin-builder-add-field>
                <i class="bi bi-plus-lg"></i> Add field
            </button>
        </div>

        <div class="plugin-builder-fields-list" data-plugin-builder-fields>
            @foreach (old('fields', $fields) as $index => $field)
                @include('loom-plugin-builder::_field-row', [
                    'index' => $index,
                    'field' => $field,
                    'fieldTypes' => $fieldTypes,
                ])
            @endforeach
        </div>
    </div>

    <template data-plugin-builder-field-template>
        @include('loom-plugin-builder::_field-row', [
            'index' => '__INDEX__',
            'field' => [
                'name' => '',
                'label' => '',
                'type' => 'text',
                'colClass' => 'col-12',
                'validation_rules' => [],
            ],
            'fieldTypes' => $fieldTypes,
        ])
    </template>

    <template data-plugin-builder-validation-rule-template>
        <div class="row g-2 mb-2 align-items-end" data-plugin-builder-validation-rule>
            <div class="col-md-5">
                <input type="text"
                       name="fields[__INDEX__][validation_rules][__RULE_INDEX__][rule]"
                       class="form-control font-monospace"
                       placeholder="required">
            </div>
            <div class="col-md-6">
                <input type="text"
                       name="fields[__INDEX__][validation_rules][__RULE_INDEX__][message]"
                       class="form-control"
                       placeholder="Custom message (optional)">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" data-plugin-builder-remove-validation-rule>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </template>

    <div class="loom-form-actions mt-4">
        <button type="submit" class="loom-form-btn loom-form-btn--primary" data-plugin-builder-submit>
            Save plugin
        </button>
        <a href="{{ route('loom.plugin-builder.index') }}" class="loom-form-btn loom-form-btn--secondary">Cancel</a>
    </div>
</form>

<div class="plugin-builder-save-overlay" data-plugin-builder-save-overlay hidden aria-hidden="true" aria-live="polite">
    <div class="plugin-builder-save-overlay__card" role="status">
        <h3 class="plugin-builder-save-overlay__title">Saving plugin</h3>
        <div class="progress plugin-builder-save-overlay__progress" aria-hidden="true">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 data-plugin-builder-save-progress
                 style="width: 0%"></div>
        </div>
        <p class="plugin-builder-save-overlay__step" data-plugin-builder-save-step>Validating…</p>
    </div>
</div>
