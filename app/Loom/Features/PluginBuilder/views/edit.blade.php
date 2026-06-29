@extends('admin.layout')

@section('title', 'Edit plugin')
@section('page-title', 'Edit plugin')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>{{ $definition['plugin']['label'] ?? $pluginSlug }}</h2>
        </div>
        <div class="admin-panel-body p-4">
            @if (session('migrate_output'))
                <pre class="small admin-terminal-output p-3 mb-3">{{ session('migrate_output') }}</pre>
            @endif

            @include('loom-plugin-builder::_form', [
                'pluginSlug' => $pluginSlug,
                'definition' => $definition,
                'fieldTypes' => $fieldTypes,
                'tablePrefix' => $tablePrefix,
            ])
        </div>
    </div>
@endsection
