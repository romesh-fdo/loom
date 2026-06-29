@extends('admin.layout')

@section('title', 'Assets')
@section('page-title', 'Assets')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
    @vite(['resources/css/assets-file-manager.css'])
@endpush

@section('content')
    <div class="admin-panel assets-panel" data-bs-theme="dark">
        <div class="admin-panel-header">
            <h2>Assets</h2>
            <a href="{{ $assetsUrl }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Open public folder
            </a>
        </div>
        <div class="admin-panel-body p-0">
            <div id="fm-main-block" class="assets-file-manager-wrap">
                <div id="fm"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const block = document.getElementById('fm-main-block');

            if (block) {
                const top = block.getBoundingClientRect().top;
                block.style.height = Math.max(480, window.innerHeight - top - 16) + 'px';
            }

            const script = document.createElement('script');
            script.src = @json(asset('vendor/file-manager/js/file-manager.js'));
            document.body.appendChild(script);
        });
    </script>
@endpush
