<div class="modal fade loom-media-finder-modal"
     id="loom-media-finder-modal"
     tabindex="-1"
     aria-labelledby="loom-media-finder-modal-label"
     aria-hidden="true"
     data-media-finder-modal
     data-prepare-url="{{ route('loom.media.prepare-picker') }}"
     data-file-manager-css="{{ asset('vendor/file-manager/css/file-manager.css') }}">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content" data-bs-theme="dark">
            <div class="modal-header">
                <h5 class="modal-title" id="loom-media-finder-modal-label">Select file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" data-media-finder-modal-body>
                <div id="fm-main-block"
                     class="media-file-manager-wrap"
                     data-file-manager-src="{{ asset('vendor/file-manager/js/file-manager.js') }}">
                    <div id="fm"></div>
                </div>
            </div>
            <div class="modal-footer">
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-x-lg',
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'type' => 'button',
                    'attributes' => ['data-bs-dismiss' => 'modal'],
                ])
            </div>
        </div>
    </div>
</div>
