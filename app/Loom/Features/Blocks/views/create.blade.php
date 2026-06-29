@extends('admin.layout')

@section('title', 'Add block')
@section('page-title', 'Add block')

@section('content')
    <div class="admin-panel">
        @include('loom-blocks::_panel-header', ['panelTitle' => 'Add block'])
        <div class="admin-panel-body p-4">
            @include('loom-blocks::_form')
        </div>
    </div>
@endsection
