@extends('admin.layout')

@section('title', 'Plugin Builder')
@section('page-title', 'Plugin Builder')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Plugins</h2>
            <a href="{{ route('loom.plugin-builder.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New plugin
            </a>
        </div>
        <div class="admin-panel-body p-4">
            <div class="row g-3">
                @forelse ($plugins as $plugin)
                    <div class="col-sm-6 col-lg-4">
                        <a href="{{ $plugin['url'] }}" class="text-decoration-none text-body">
                            <div class="stat-card h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi {{ $plugin['icon'] }} fs-4"></i>
                                    <p class="stat-card-label mb-0">{{ $plugin['label'] }}</p>
                                </div>
                                <p class="text-muted small mb-0">{{ $plugin['id'] }}</p>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted mb-0">No plugins installed yet.</p></div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
