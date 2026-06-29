@php
    $flashMessages = collect([
        'success' => ['label' => 'Success', 'icon' => 'bi-check-circle-fill', 'class' => 'text-success'],
        'error' => ['label' => 'Error', 'icon' => 'bi-exclamation-circle-fill', 'class' => 'text-danger'],
        'warning' => ['label' => 'Warning', 'icon' => 'bi-exclamation-triangle-fill', 'class' => 'text-warning'],
        'info' => ['label' => 'Info', 'icon' => 'bi-info-circle-fill', 'class' => 'text-primary'],
    ])->filter(fn ($meta, $key) => session()->has($key));
@endphp

@if ($flashMessages->isNotEmpty())
    @foreach ($flashMessages as $key => $meta)
        <div class="toast show admin-flash-toast"
             role="alert"
             aria-live="assertive"
             aria-atomic="true"
             data-bs-delay="5000">
            <div class="toast-header">
                <i class="bi {{ $meta['icon'] }} {{ $meta['class'] }} me-2"></i>
                <strong class="me-auto">{{ $meta['label'] }}</strong>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="toast"
                        aria-label="Close"></button>
            </div>
            <div class="toast-body">
                {{ session($key) }}
            </div>
        </div>
    @endforeach
@endif
