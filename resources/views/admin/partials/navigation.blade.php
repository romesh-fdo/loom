@foreach ($adminNavigation ?? [] as $key => $item)
    @php
        $sideMenu = $item['sideMenu'] ?? [];
        $hasSideMenu = count($sideMenu) > 0;
        $groupActive = $hasSideMenu && collect($sideMenu)->contains(
            fn ($child) => isset($child['route']) && request()->routeIs($child['route'])
        );
        $groupOpen = $groupActive;
    @endphp

    @if ($hasSideMenu)
        <div class="admin-nav-group {{ $groupOpen ? 'open' : '' }}" data-nav-group>
            <button type="button"
                    class="admin-nav-link admin-nav-parent {{ $groupActive ? 'active' : '' }}"
                    aria-expanded="{{ $groupOpen ? 'true' : 'false' }}">
                <span class="admin-nav-label">
                    @if (!empty($item['icon']))
                        <i class="bi {{ $item['icon'] }}"></i>
                    @endif
                    {{ $item['label'] ?? $key }}
                </span>
                <i class="bi bi-chevron-down admin-nav-chevron"></i>
            </button>
            <div class="admin-nav-children">
                @foreach ($sideMenu as $childKey => $child)
                    <a href="{{ $child['url'] ?? '#' }}"
                       class="admin-nav-link admin-nav-child {{ isset($child['route']) && request()->routeIs($child['route']) ? 'active' : '' }}">
                        @if (!empty($child['icon']))
                            <i class="bi {{ $child['icon'] }}"></i>
                        @endif
                        {{ $child['label'] ?? $childKey }}
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <a href="{{ $item['url'] ?? '#' }}"
           class="admin-nav-link {{ isset($item['route']) && request()->routeIs($item['route']) ? 'active' : '' }}">
            @if (!empty($item['icon']))
                <i class="bi {{ $item['icon'] }}"></i>
            @endif
            {{ $item['label'] ?? $key }}
        </a>
    @endif
@endforeach
