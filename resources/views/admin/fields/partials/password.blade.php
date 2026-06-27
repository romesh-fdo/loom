@php
    $type = 'password';
    $value = $value ?? old($name, '');
@endphp
@include('admin.fields.partials._input')
