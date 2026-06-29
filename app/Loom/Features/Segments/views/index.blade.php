@extends('admin.layout')

@section('title', 'Segments')
@section('page-title', 'Segments')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Segments</h2>
            <a href="{{ route('loom.segments.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Add segment
            </a>
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.segments.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search segments by name…">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <div class="theme-grid">
                <div class="row g-3">
                    @forelse ($segments as $segment)
                        <div class="col-6 col-md-4 col-lg-3">
                            <article class="theme-card segment-card h-100 {{ ($segment->enabled ?? true) ? '' : 'segment-card-disabled' }}">
                                <div class="theme-card-media segment-card-media">
                                    <span class="segment-card-icon" aria-hidden="true">
                                        <i class="bi bi-layout-split"></i>
                                    </span>
                                    @if ($segment->enabled ?? true)
                                        <span class="theme-card-badge badge-status success">On</span>
                                    @else
                                        <span class="theme-card-badge badge-status">Off</span>
                                    @endif
                                </div>
                                <div class="theme-card-content">
                                    <h3 class="theme-card-title">{{ $segment->name }}</h3>
                                    <p class="theme-card-slug">{{ $slotLabels[$segment->slot] ?? $segment->slot }}</p>
                                    <div class="theme-card-actions d-flex gap-2">
                                        <a href="{{ route('loom.segments.edit', $segment->slug) }}"
                                           class="btn btn-sm btn-outline-secondary flex-fill">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">No segments for this theme yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($segments->hasPages())
                <div class="mt-4">
                    {{ $segments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
