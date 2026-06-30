@extends('admin.layout')

@section('title', 'Segments')
@section('page-title', 'Segments')

@push('styles')
    @vite(['resources/css/segments-tree.css'])
@endpush

@section('content')
    @php
        $adminPrefix = config('loom.admin.route_prefix', 'admin');
    @endphp
    <div class="admin-panel segments-panel"
         id="segments-explorer"
         data-tree-url="{{ route('loom.segments.tree') }}"
         data-form-create-url="{{ route('loom.segments.form.create') }}"
         data-form-edit-base="{{ url($adminPrefix.'/segments/form') }}"
         data-panel-destroy-base="{{ url($adminPrefix.'/segments/panel') }}"
         data-folders-base="{{ url($adminPrefix.'/segments/folders') }}"
         data-index-url="{{ route('loom.segments.index') }}"
         data-initial-segment="{{ $initialSegment }}"
         data-initial-create="{{ $initialCreate ? '1' : '0' }}"
         data-initial-folder="{{ $initialFolder }}">
        <div class="admin-panel-body p-0 segments-explorer-body">
            <aside class="segments-tree-panel" aria-label="Segment folders">
                <div class="segments-tree-scroll">
                    <ul class="segments-tree" id="segments-tree" role="tree"></ul>
                </div>
            </aside>
            <section class="segments-form-panel" aria-label="Segment editor">
                <div class="segments-form-empty" id="segments-form-empty">
                    <i class="bi bi-layout-split segments-form-empty-icon" aria-hidden="true"></i>
                    <p class="mb-0 text-muted">Select a folder or segment from the tree.</p>
                </div>
                <div class="segments-folder-context d-none" id="segments-folder-context" aria-live="polite"></div>
                <div class="segments-form-content d-none" id="segments-form-content"></div>
            </section>
        </div>
    </div>

    @include('admin.partials.input-modal')

    <div class="modal fade segments-folder-modal"
         id="segments-folder-modal"
         tabindex="-1"
         aria-labelledby="segments-folder-modal-title"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="segments-folder-modal-title" data-segments-folder-title>Move to folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-segments-folder-cancel aria-label="Close"></button>
                </div>
                <form data-segments-folder-form>
                    <div class="modal-body">
                        <label class="form-label" for="segments-folder-select">Destination folder</label>
                        <select class="form-select" id="segments-folder-select" data-segments-folder-select></select>
                    </div>
                    <div class="modal-footer admin-action-group">
                        @include('admin.partials.action-submit', [
                            'icon' => 'bi-x-lg',
                            'label' => 'Cancel',
                            'variant' => 'secondary',
                            'type' => 'button',
                            'attributes' => [
                                'data-bs-dismiss' => 'modal',
                                'data-segments-folder-cancel' => '',
                            ],
                        ])
                        @include('admin.partials.action-submit', [
                            'icon' => 'bi-check-lg',
                            'label' => 'Move',
                            'variant' => 'primary',
                            'type' => 'submit',
                            'attributes' => ['data-segments-folder-accept' => ''],
                        ])
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/segments-tree.js'])
@endpush
