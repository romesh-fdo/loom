@extends('admin.layout')

@section('title', 'Edit asdasd')
@section('page-title', 'Edit asdasd')

@section('content')
    <div class="admin-panel">
        @include('loom-asdasd::_panel-header', ['panelTitle' => 'Edit asdasd'])
        <div class="admin-panel-body p-4">
            @include('loom-asdasd::_form')
        </div>
    </div>
@endsection
