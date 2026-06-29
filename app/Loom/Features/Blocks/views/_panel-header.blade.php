@php
    $hasConfigModal = collect($forms ?? [])->contains(
        fn ($panel) => ($panel['meta']['layout'] ?? 'panel') === 'modal'
    );
@endphp

<div class="admin-panel-header">
    <h2>{{ $panelTitle }}</h2>
    @if ($hasConfigModal)
        <button type="button"
                class="loom-block-settings-btn"
                data-bs-toggle="modal"
                data-bs-target="#block-config-modal"
                aria-label="Block settings">
            <i class="bi bi-gear"></i>
        </button>
    @endif
</div>
