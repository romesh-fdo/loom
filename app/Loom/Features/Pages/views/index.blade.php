@extends('admin.layout')

@section('title', 'Pages')
@section('page-title', 'Pages')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Pages</h2>
            <a href="{{ route('loom.pages.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Add page
            </a>
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.pages.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search pages by name…">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <div class="row g-3">
                @forelse ($pages as $page)
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card h-100">
                            <p class="stat-card-label mb-1">{{ $page->name }}</p>
                            <p class="text-muted small mb-3">/{{ $page->url }}</p>
                            <a href="{{ route('loom.pages.edit', $page) }}" class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted mb-0">No pages found.</p>
                    </div>
                @endforelse
            </div>

            @if ($pages->hasPages())
                <div class="mt-4">
                    {{ $pages->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
