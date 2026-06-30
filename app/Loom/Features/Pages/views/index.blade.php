@extends('admin.layout')

@section('title', 'Pages')
@section('page-title', 'Pages')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            @include('admin.partials.action-link', [
                'href' => route('loom.pages.create'),
                'icon' => 'bi-plus-lg',
                'label' => 'Add page',
                'variant' => 'primary',
            ])
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.pages.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search pages by name…">
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-search',
                        'label' => 'Search',
                        'variant' => 'input',
                        'type' => 'submit',
                    ])
                </div>
            </form>

            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                @forelse ($pages as $page)
                    <div class="col">
                        <div class="stat-card stat-card-compact h-100">
                            <p class="stat-card-label mb-1">{{ $page->name }}</p>
                            <p class="text-muted small mb-1">{{ ($page->url ?? '') === '' ? '/' : '/'.$page->url }}</p>
                            @php
                                $layoutSlug = (string) ($page->layout ?? '');
                                $layoutLabel = $layoutNames[$layoutSlug] ?? ($layoutSlug !== '' ? $layoutSlug : null);
                            @endphp
                            @if ($layoutLabel)
                                <p class="text-muted small mb-3">Layout: {{ $layoutLabel }}</p>
                            @else
                                <p class="text-muted small mb-3">Layout: <span class="text-warning">Not set</span></p>
                            @endif
                            <div class="admin-action-group">
                                @include('admin.partials.action-link', [
                                    'href' => url('/'.ltrim((string) $page->url, '/')),
                                    'icon' => 'bi-box-arrow-up-right',
                                    'label' => 'View',
                                    'variant' => 'primary',
                                    'target' => '_blank',
                                    'rel' => 'noopener noreferrer',
                                ])
                                @include('admin.partials.action-link', [
                                    'href' => route('loom.pages.edit', $page->slug),
                                    'icon' => 'bi-pencil',
                                    'label' => 'Edit',
                                    'variant' => 'muted',
                                ])
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted mb-0">No pages for this theme yet.</p>
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
