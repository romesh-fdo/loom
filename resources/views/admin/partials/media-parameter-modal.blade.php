<div class="modal fade loom-media-parameter-modal"
     id="loom-media-parameter-modal"
     tabindex="-1"
     aria-labelledby="loom-media-parameter-modal-label"
     aria-hidden="true"
     data-media-parameter-modal
     data-upload-url="{{ route('loom.media.upload') }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loom-media-parameter-modal-label">Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="loom-media-parameter-modal__preview mb-3"
                     data-media-parameter-modal-preview
                     hidden>
                    <div class="loom-media-parameter-modal__preview-image d-none" data-media-parameter-modal-preview-image>
                        <img src="" alt="" data-media-parameter-modal-preview-img>
                    </div>
                    <div class="loom-media-parameter-modal__preview-file d-none" data-media-parameter-modal-preview-file>
                        <i class="bi bi-file-earmark" aria-hidden="true"></i>
                        <span data-media-parameter-modal-preview-name></span>
                    </div>
                </div>

                <div class="loom-media-parameter-modal__empty mb-3 text-muted small"
                     data-media-parameter-modal-empty>
                    No file selected yet.
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3 admin-action-group">
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-upload',
                        'label' => 'Upload file',
                        'variant' => 'primary',
                        'type' => 'button',
                        'extraClass' => 'admin-action-submit--compact',
                        'attributes' => ['data-media-parameter-upload-trigger' => ''],
                    ])
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-folder2-open',
                        'label' => 'Select from library',
                        'variant' => 'secondary',
                        'type' => 'button',
                        'extraClass' => 'admin-action-submit--compact',
                        'attributes' => ['data-media-parameter-library-trigger' => ''],
                    ])
                    <input type="file"
                           class="d-none"
                           data-media-parameter-upload-input
                           accept="image/*,.pdf,.doc,.docx">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="loom-media-parameter-modal-alt">Alt text</label>
                        <input type="text"
                               class="form-control"
                               id="loom-media-parameter-modal-alt"
                               data-media-parameter-modal-alt
                               placeholder="Describe the image">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="loom-media-parameter-modal-class">CSS class</label>
                        <input type="text"
                               class="form-control"
                               id="loom-media-parameter-modal-class"
                               data-media-parameter-modal-class
                               placeholder="e.g. img-fluid">
                    </div>
                </div>
            </div>
            <div class="modal-footer admin-action-group">
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-eraser',
                    'label' => 'Clear',
                    'variant' => 'danger',
                    'type' => 'button',
                    'extraClass' => 'me-auto',
                    'attributes' => ['data-media-parameter-modal-clear' => ''],
                ])
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-x-lg',
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'type' => 'button',
                    'attributes' => ['data-bs-dismiss' => 'modal'],
                ])
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-check-lg',
                    'label' => 'Done',
                    'variant' => 'primary',
                    'type' => 'button',
                    'attributes' => ['data-media-parameter-modal-apply' => ''],
                ])
            </div>
        </div>
    </div>
</div>
