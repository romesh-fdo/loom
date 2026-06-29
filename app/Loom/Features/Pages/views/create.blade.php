@extends('admin.layout')

@section('title', 'Add page')
@section('page-title', 'Add page')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Add page</h2>
        </div>
        <div class="admin-panel-body p-4">
            @include('loom-pages::_form')
        </div>
    </div>
@endsection
