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
            <a href="{{ route('admin.settings', ['tab' => 'theme']) }}" class="btn btn-sm btn-outline-secondary" aria-label="Back">
                <i class="bi bi-arrow-left"></i>
            </a>
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

                        <div class="mb-4">
                            <label for="theme-slug" class="form-label">Slug</label>
                            <input type="text"
                                   id="theme-slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   pattern="[a-z0-9-]+"
                                   required>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Create</button>
                            <a href="{{ route('admin.settings', ['tab' => 'theme']) }}" class="btn btn-outline-secondary">Cancel</a>
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
