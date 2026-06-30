@extends('admin.layout')

@section('title', 'Blocks')
@section('page-title', 'Blocks')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            @include('admin.partials.action-link', [
                'href' => route('loom.blocks.create'),
                'icon' => 'bi-plus-lg',
                'label' => 'Add block',
                'variant' => 'primary',
            ])
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.blocks.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           class="form-control"
                           placeholder="Search blocks by name…">
                    @include('admin.partials.action-submit', [
                        'icon' => 'bi-search',
                        'label' => 'Search',
                        'variant' => 'input',
                        'type' => 'submit',
                    ])
                </div>
            </form>

            <div class="row g-3">
                @forelse ($blocks as $block)
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card h-100">
                            <p class="stat-card-label mb-1">{{ $block->name }}</p>
                            <p class="text-muted small mb-3">Updated {{ $block->updatedAt()->diffForHumans() }}</p>
                            @include('admin.partials.action-link', [
                                'href' => route('loom.blocks.edit', $block->slug),
                                'icon' => 'bi-pencil',
                                'label' => 'Edit',
                                'variant' => 'muted',
                            ])
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted mb-0">No blocks for this theme yet.</p>
                    </div>
                @endforelse
            </div>

            @if ($blocks->hasPages())
                <div class="mt-4">
                    {{ $blocks->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
