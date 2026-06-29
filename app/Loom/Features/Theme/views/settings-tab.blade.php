<div class="theme-grid">
    <div class="row g-3">
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.settings.theme.create') }}" class="theme-card theme-card-add" aria-label="Add theme">
                <div class="theme-card-media theme-card-media-add">
                    <span class="theme-card-add-icon" aria-hidden="true">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                </div>
            </a>
        </div>

        @foreach ($themes as $theme)
            <div class="col-6 col-md-4 col-lg-3">
                <article class="theme-card {{ ($theme['slug'] ?? '') === $activeTheme ? 'theme-card-active' : '' }}">
                    <div class="theme-card-media">
                        @if (! empty($theme['preview_url']))
                            <img src="{{ $theme['preview_url'] }}"
                                 alt="{{ $theme['name'] ?? 'Theme' }}"
                                 class="theme-card-image">
                        @else
                            <div class="theme-card-placeholder">
                                <i class="bi bi-image" aria-hidden="true"></i>
                            </div>
                        @endif

                        @if (($theme['slug'] ?? '') === $activeTheme)
                            <span class="theme-card-badge badge-status success">Active</span>
                        @endif
                    </div>

                    <div class="theme-card-content">
                        <h3 class="theme-card-title">{{ $theme['name'] ?? '—' }}</h3>

                        @if (($theme['slug'] ?? '') !== $activeTheme)
                            <form method="POST"
                                  action="{{ route('admin.settings.theme.activate', $theme['slug']) }}"
                                  class="theme-card-foot">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    Activate
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</div>
