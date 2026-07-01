{{-- loom:meta
{
    "name": "Test Page",
    "slug": "test-page",
    "url": "product/{id}",
    "layout": "custom",
    "updated_at": "2026-07-01T15:59:07+00:00"
}
--}}

@php
    $productDetails = loom_import('loom.asdasd', 'getFirst', []);

    $layoutFields = ['meta' => ['author' => ($productDetails && isset($productDetails->id)) ? $productDetails->id : '', 'description' => ($productDetails && isset($productDetails->created_at)) ? $productDetails->created_at : '']];
@endphp

@verbatim
@block('hero', ['hero_header' => 'Hello', 'hero_main_text' => 'FOOD', 'intro_text' => '<p>Delicious Fast Food</p><p>for Every Moment</p>', 'intro_paragraph' => 'Experience bold flavors crafted from premium ingredients. From crispy burgers to gourmet pizzas - every bite is an adventure worth savoring.', 'youtube_link' => ['url' => 'https://www.youtube.com/watch?v=RXv_uIN6e-Y', 'class' => 'magnific_popup btn-play popup-youtube', 'id' => '', 'target' => ''], 'story_text' => 'Watch Our Story', 'hero_image' => ['url' => 'http://loom.test/media/Leaflet 2.png', 'alt' => '', 'class' => '']])
@endverbatim
