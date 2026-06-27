@php
    $flashMessages = collect([
        'success' => ['label' => 'Success', 'icon' => 'bi-check-circle-fill', 'class' => 'text-success'],
        'error' => ['label' => 'Error', 'icon' => 'bi-exclamation-circle-fill', 'class' => 'text-danger'],
        'warning' => ['label' => 'Warning', 'icon' => 'bi-exclamation-triangle-fill', 'class' => 'text-warning'],
        'info' => ['label' => 'Info', 'icon' => 'bi-info-circle-fill', 'class' => 'text-primary'],
    ])->filter(fn ($meta, $key) => session()->has($key));
@endphp

@if ($flashMessages->isNotEmpty())
    <div class="toast-container position-fixed top-0 end-0 p-3 admin-flash-toasts" aria-live="polite" aria-atomic="true">
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
    </div>

    <script>
        (function () {
            document.querySelectorAll('.admin-flash-toast').forEach(function (toast) {
                var delay = parseInt(toast.dataset.bsDelay || '5000', 10);
                var hideTimer = window.setTimeout(function () {
                    toast.classList.remove('show');
                    window.setTimeout(function () {
                        toast.remove();
                    }, 300);
                }, delay);

                var closeBtn = toast.querySelector('[data-bs-dismiss="toast"]');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        window.clearTimeout(hideTimer);
                        toast.classList.remove('show');
                        window.setTimeout(function () {
                            toast.remove();
                        }, 300);
                    });
                }
            });
        })();
    </script>
@endif
