@extends('admin.layout')

@section('title', 'Assets')
@section('page-title', 'Assets')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
    @vite(['resources/css/assets-file-manager.css'])
@endpush

@section('content')
    <div class="admin-panel assets-panel" data-bs-theme="dark">
        <div class="admin-panel-body p-0">
            <div id="fm-main-block"
                 class="assets-file-manager-wrap"
                 data-file-manager-src="{{ asset('vendor/file-manager/js/file-manager.js') }}">
                <div id="fm"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/file-manager-page.js'])
@endpush
