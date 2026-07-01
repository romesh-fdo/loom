{{-- loom:meta
{
    "name": "Defualt Layout",
    "slug": "defualt-layout",
    "updated_at": "2026-06-30T16:52:43+00:00"
}
--}}

@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
   @segment('meta', [])
   @segment('header/seo', [])
   @segment('css', [])
</head>
<body>
   @segment('header', [])
   <main>{{ $content }}</main>
   @segment('search-overlay', [])
   @segment('scroll-to-top', [])
   @segment('scripts', [])
</body>
</html>
@endverbatim
