@extends('admin.layout')

@section('title', 'Layouts')
@section('page-title', 'Layouts')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            @include('admin.partials.action-link', [
                'href' => route('loom.layouts.create'),
                'icon' => 'bi-plus-lg',
                'label' => 'Add layout',
                'variant' => 'primary',
            ])
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.layouts.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search layouts by name…">
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-search',
                        'label' => 'Search',
                        'variant' => 'input',
                        'type' => 'submit',
                    ])
                </div>
            </form>

            <div class="row g-3">
                @forelse ($layouts as $layout)
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card h-100">
                            <p class="stat-card-label mb-1">{{ $layout->name }}</p>
                            <p class="text-muted small mb-3">Updated {{ $layout->updatedAt()->diffForHumans() }}</p>
                            @include('admin.partials.action-link', [
                                'href' => route('loom.layouts.edit', $layout->slug),
                                'icon' => 'bi-pencil',
                                'label' => 'Edit',
                                'variant' => 'muted',
                            ])
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted mb-0">No layouts for this theme yet.</p>
                    </div>
                @endforelse
            </div>

            @if ($layouts->hasPages())
                <div class="mt-4">
                    {{ $layouts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
