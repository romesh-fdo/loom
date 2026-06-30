<div class="modal fade loom-url-parameter-modal"
     id="loom-url-parameter-modal"
     tabindex="-1"
     aria-labelledby="loom-url-parameter-modal-label"
     aria-hidden="true"
     data-url-parameter-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loom-url-parameter-modal-label">Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="loom-url-parameter-modal-url">URL</label>
                    <input type="url"
                           class="form-control"
                           id="loom-url-parameter-modal-url"
                           data-url-parameter-modal-url
                           placeholder="https://example.com">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="loom-url-parameter-modal-class">CSS class</label>
                        <input type="text"
                               class="form-control"
                               id="loom-url-parameter-modal-class"
                               data-url-parameter-modal-class
                               placeholder="e.g. btn btn-primary">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="loom-url-parameter-modal-id">HTML id</label>
                        <input type="text"
                               class="form-control"
                               id="loom-url-parameter-modal-id"
                               data-url-parameter-modal-id
                               placeholder="e.g. hero-cta">
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input type="checkbox"
                           class="form-check-input"
                           id="loom-url-parameter-modal-target"
                           data-url-parameter-modal-target>
                    <label class="form-check-label" for="loom-url-parameter-modal-target">Open in new tab</label>
                </div>
            </div>
            <div class="modal-footer admin-action-group">
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-eraser',
                    'label' => 'Clear',
                    'variant' => 'danger',
                    'type' => 'button',
                    'extraClass' => 'me-auto',
                    'attributes' => ['data-url-parameter-modal-clear' => ''],
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
                    'attributes' => ['data-url-parameter-modal-apply' => ''],
                ])
            </div>
        </div>
    </div>
</div>
