@extends('admin.layout')

@section('title', 'Edit segment')
@section('page-title', 'Edit segment')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Edit segment</h2>
        </div>
        <div class="admin-panel-body p-4">
            @include('loom-segments::_form')
        </div>
    </div>
@endsection
