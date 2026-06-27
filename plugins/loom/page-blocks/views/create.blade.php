@extends('admin.layout')

@section('title', 'Add Page Block')
@section('page-title', 'Add Page Block')

@section('content')
    <div class="admin-panel">
        @include('loom-page-blocks::_panel-header', ['panelTitle' => 'Add Page Block'])
        <div class="admin-panel-body p-4">
            @include('loom-page-blocks::_form')
        </div>
    </div>
@endsection
