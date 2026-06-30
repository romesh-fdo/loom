<div class="modal fade admin-input-modal"
     id="admin-input-modal"
     tabindex="-1"
     aria-labelledby="admin-input-modal-title"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admin-input-modal-title" data-admin-input-title>Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-admin-input-cancel aria-label="Close"></button>
            </div>
            <form data-admin-input-form>
                <div class="modal-body">
                    <label class="form-label" for="admin-input-modal-field" data-admin-input-label>Folder name</label>
                    <input type="text"
                           class="form-control"
                           id="admin-input-modal-field"
                           data-admin-input-field
                           autocomplete="off"
                           required>
                    <div class="form-text text-muted d-none" data-admin-input-hint></div>
                    <div class="invalid-feedback" data-admin-input-error></div>
                </div>
                <div class="modal-footer admin-action-group">
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-x-lg',
                        'label' => 'Cancel',
                        'variant' => 'secondary',
                        'type' => 'button',
                        'attributes' => [
                            'data-bs-dismiss' => 'modal',
                            'data-admin-input-cancel' => '',
                        ],
                    ])
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-check-lg',
                        'label' => 'Save',
                        'variant' => 'primary',
                        'type' => 'submit',
                        'attributes' => ['data-admin-input-accept' => ''],
                    ])
                </div>
            </form>
        </div>
    </div>
</div>
