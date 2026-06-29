@extends('admin.layout')

@section('title', 'New plugin')
@section('page-title', 'New plugin')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>New plugin</h2>
        </div>
        <div class="admin-panel-body p-4">
            @if (session('migrate_output'))
                <pre class="small admin-terminal-output p-3 mb-3">{{ session('migrate_output') }}</pre>
            @endif

            @include('loom-plugin-builder::_form', [
                'pluginSlug' => null,
                'fieldTypes' => $fieldTypes,
                'tablePrefix' => $tablePrefix,
                'definition' => [
                    'is_new' => true,
                    'plugin' => ['name' => '', 'label' => '', 'route' => '', 'icon' => 'bi-box'],
                    'model' => ['class' => '', 'table' => ''],
                    'forms' => [['fields' => []]],
                ],
            ])
        </div>
    </div>
@endsection
