@extends('admin.layout')

@section('title', 'Edit page')
@section('page-title', 'Edit page')

@section('content')
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h2>Edit page</h2>
        </div>
        <div class="admin-panel-body p-4">
            @include('loom-pages::_form')
        </div>
    </div>
@endsection
