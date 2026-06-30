{{-- loom:meta
{
    "name": "Custom",
    "slug": "custom",
    "updated_at": "2026-06-30T17:27:23+00:00"
}
--}}

@verbatim
<!DOCTYPE html>
<html lang="en">
  <head>
     @segment('meta', ['author' => 'Sarab', 'description' => 'Sarab - Fast Food & Restaurant HTML Template'])
     @segment('header/seo', [])
     @segment('css', [])
  </head>
  <body>
     @segment('header', [])
     {{ $content }}
     @segment('search-overlay', [])
     @segment('scroll-to-top', [])
     @segment('scripts', [])
  </body>
</html>
@endverbatim
