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
            <div>
                <h2>Assets</h2>
                <p class="text-muted small mb-0">
                    Active theme: <strong>{{ $activeTheme['name'] ?? $activeThemeSlug }}</strong>.
                    Files are stored in
                    <code>/{{ trim(config('loom.assets.public_path', 'theme/default/assets'), '/') }}</code>.
                </p>
            </div>
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
