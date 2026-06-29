@extends('admin.layout')

@section('title', 'Settings')
@section('page-title', 'Settings')

@push('styles')
    @vite(['resources/css/theme-settings.css'])
@endpush

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Settings</h2>
        </div>
        <div class="admin-panel-body p-0">
            <ul class="nav nav-tabs admin-settings-tabs px-3 pt-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'theme' ? 'active' : '' }}"
                            id="settings-theme-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#settings-theme-pane"
                            type="button"
                            role="tab"
                            aria-controls="settings-theme-pane"
                            aria-selected="{{ $activeTab === 'theme' ? 'true' : 'false' }}">
                        <i class="bi bi-palette-fill me-1"></i> Theme
                    </button>
                </li>
            </ul>

            <div class="tab-content admin-settings-tab-content">
                <div class="tab-pane fade {{ $activeTab === 'theme' ? 'show active' : '' }}"
                     id="settings-theme-pane"
                     role="tabpanel"
                     aria-labelledby="settings-theme-tab"
                     tabindex="0">
                    @include('loom-theme::settings-tab')
                </div>
            </div>
        </div>
    </div>
@endsection
