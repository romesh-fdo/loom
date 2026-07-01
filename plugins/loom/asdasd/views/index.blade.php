@extends('admin.layout')

@section('title', 'asdasd')
@section('page-title', 'asdasd')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>asdasd</h2>
            <a href="{{ route('loom.asdasd.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Add {{ strtolower($label ?? 'asdasd') }}
            </a>
        </div>
        <div class="admin-panel-body p-3">
            <form method="GET" action="{{ route('loom.asdasd.index') }}" class="mb-4">
                <div class="input-group">
                    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search…">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <div class="row g-3">
                @forelse ($asdasds as $asdasd)
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card h-100">
                            <p class="stat-card-label mb-1">{{ $asdasd->title ?: ('#' . $asdasd->id) }}</p>
                            <p class="text-muted small mb-3">Updated {{ $asdasd->updated_at->diffForHumans() }}</p>
                            <a href="{{ route('loom.asdasd.edit', $asdasd) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted mb-0">No records found.</p></div>
                @endforelse
            </div>

            @if ($asdasds->hasPages())
                <div class="mt-4">{{ $asdasds->links() }}</div>
            @endif
        </div>
    </div>
@endsection
