@extends('admin.layout')

@section('title', 'Add segment')
@section('page-title', 'Add segment')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Add segment</h2>
        </div>
        <div class="admin-panel-body p-4">
            @include('loom-segments::_form')
        </div>
    </div>
@endsection
