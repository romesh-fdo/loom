@extends('admin.layout')

@section('title', 'Add theme')
@section('page-title', 'Add theme')

@push('styles')
    @vite(['resources/css/theme-settings.css'])
@endpush

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Add theme</h2>
            @include('admin.partials.action-link', [
                'href' => route('admin.settings', ['tab' => 'theme']),
                'icon' => 'bi-arrow-left',
                'label' => 'Themes',
                'variant' => 'muted',
            ])
        </div>
        <div class="admin-panel-body p-4">
            <form method="POST"
                  action="{{ route('admin.settings.theme.store') }}"
                  enctype="multipart/form-data"
                  class="theme-create-form">
                @csrf

                <div class="row g-4">
                    <div class="col-lg-5">
                        <label for="theme-image" class="theme-upload-zone" id="theme-image-preview" aria-label="Upload image">
                            <div class="theme-upload-zone-inner">
                                <span class="theme-upload-icon" aria-hidden="true">
                                    <i class="bi bi-image"></i>
                                </span>
                            </div>
                        </label>
                        <input type="file"
                               id="theme-image"
                               name="image"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               class="visually-hidden @error('image') is-invalid @enderror">
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label for="theme-name" class="form-label">Name</label>
                            <input type="text"
                                   id="theme-name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="theme-description" class="form-label">Description</label>
                            <textarea id="theme-description"
                                      name="description"
                                      rows="3"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="theme-version" class="form-label">Version</label>
                            <input type="text"
                                   id="theme-version"
                                   name="version"
                                   value="{{ old('version', '1.0.0') }}"
                                   class="form-control @error('version') is-invalid @enderror">
                            @error('version')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="theme-author" class="form-label">Author</label>
                            <input type="text"
                                   id="theme-author"
                                   name="author"
                                   value="{{ old('author') }}"
                                   class="form-control @error('author') is-invalid @enderror">
                            @error('author')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="loom-form-actions">
                            @include('admin.partials.action-submit', [
                                'icon' => 'bi-plus-lg',
                                'label' => 'Create',
                                'variant' => 'primary',
                                'type' => 'submit',
                            ])
                            @include('admin.partials.action-link', [
                                'href' => route('admin.settings', ['tab' => 'theme']),
                                'icon' => 'bi-x-lg',
                                'label' => 'Cancel',
                                'variant' => 'muted',
                            ])
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('theme-image');
            const preview = document.getElementById('theme-image-preview');

            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', function () {
                const file = input.files?.[0];

                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.classList.add('theme-upload-zone-has-image');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="" class="theme-card-image">';
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
@endpush
