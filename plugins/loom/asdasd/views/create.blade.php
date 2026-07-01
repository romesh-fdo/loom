@extends('admin.layout')

@section('title', 'Add asdasd')
@section('page-title', 'Add asdasd')

@section('content')
    <div class="admin-panel">
        @include('loom-asdasd::_panel-header', ['panelTitle' => 'Add asdasd'])
        <div class="admin-panel-body p-4">
            @include('loom-asdasd::_form')
        </div>
    </div>
@endsection
