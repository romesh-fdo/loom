<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->name ?? config('app.name', 'Loom') }}</title>
    <link rel="stylesheet" href="{{ $assetsBase }}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ $assetsBase }}/css/all.min.css">
    <link rel="stylesheet" href="{{ $assetsBase }}/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="{{ $assetsBase }}/css/aos.css">
    <link rel="stylesheet" href="{{ $assetsBase }}/css/magnific-popup.css">
    <link rel="stylesheet" href="{{ $assetsBase }}/css/style.css">
</head>
<body>
    {!! $slots['topbar'] ?? '' !!}
    {!! $slots['header'] ?? '' !!}

    <main>
        {!! $content !!}
    </main>

    {!! $slots['footer'] ?? '' !!}
    {!! $slots['search_overlay'] ?? '' !!}
    {!! $slots['scroll_to_top'] ?? '' !!}

    <script src="{{ $assetsBase }}/js/jquery-3.7.1.min.js"></script>
    <script src="{{ $assetsBase }}/js/bootstrap.bundle.min.js"></script>
    <script src="{{ $assetsBase }}/js/swiper-bundle.min.js"></script>
    <script src="{{ $assetsBase }}/js/aos.js"></script>
    <script src="{{ $assetsBase }}/js/jquery.magnific-popup.min.js"></script>
    <script src="{{ $assetsBase }}/js/main.js"></script>

    {!! $slots['body_end'] ?? '' !!}
</body>
</html>
