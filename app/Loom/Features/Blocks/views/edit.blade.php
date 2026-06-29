@extends('admin.layout')

@section('title', 'Edit block')
@section('page-title', 'Edit block')

@section('content')
    <div class="admin-panel">
        @include('loom-blocks::_panel-header', ['panelTitle' => 'Edit block'])
        <div class="admin-panel-body p-4">
            @include('loom-blocks::_form')
        </div>
    </div>
@endsection
