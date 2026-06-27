@extends('admin.layout')

@section('title', 'Page Blocks')
@section('page-title', 'Page Blocks')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Page Blocks</h2>
            <a href="{{ route('loom.page-blocks.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Add {{ strtolower($label ?? 'Page Blocks') }}
            </a>
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.page-blocks.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search…">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <div class="row g-3">
                @forelse ($page_blocks as $page_block)
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card h-100">
                            <p class="stat-card-label mb-1">{{ $page_block->block_name ?: ('#' . $page_block->id) }}</p>
                            <p class="text-muted small mb-3">Updated {{ $page_block->updated_at->diffForHumans() }}</p>
                            <a href="{{ route('loom.page-blocks.edit', $page_block) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted mb-0">No records found.</p></div>
                @endforelse
            </div>

            @if ($page_blocks->hasPages())
                <div class="mt-4">{{ $page_blocks->links() }}</div>
            @endif
        </div>
    </div>
@endsection
