@extends('admin.layout')

@section('title', 'Add layout')
@section('page-title', 'Add layout')

@push('styles')
    @vite(['resources/css/segments-tree.css', 'resources/css/layout-form.css'])
@endpush

@section('content')
    <div class="admin-panel layout-form-panel">
        <div class="admin-panel-body p-4">
            @include('loom-layouts::_form')
        </div>
    </div>
@endsection
