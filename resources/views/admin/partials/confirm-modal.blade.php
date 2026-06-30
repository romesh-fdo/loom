<div class="modal fade admin-confirm-modal"
     id="admin-confirm-modal"
     tabindex="-1"
     aria-labelledby="admin-confirm-modal-title"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admin-confirm-modal-title" data-admin-confirm-title>Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-admin-confirm-cancel aria-label="Close"></button>
            </div>
            <div class="modal-body" data-admin-confirm-message>Are you sure?</div>
            <div class="modal-footer admin-action-group">
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-x-lg',
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'type' => 'button',
                    'attributes' => [
                        'data-bs-dismiss' => 'modal',
                        'data-admin-confirm-cancel' => '',
                    ],
                ])
                @include('admin.partials.action-submit', [
                    'icon' => 'bi-check-lg',
                    'label' => 'Confirm',
                    'variant' => 'danger',
                    'type' => 'button',
                    'attributes' => ['data-admin-confirm-accept' => ''],
                ])
            </div>
        </div>
    </div>
</div>
