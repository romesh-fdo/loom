{{-- loom:meta
{
    "name": "Hero",
    "slug": "hero",
    "parameters": [
        {
            "name": "hero_header",
            "label": "Hero Header",
            "type": "text",
            "default": "#1 Rated Fast Food Restaurant in New York",
            "colClass": "col-md-3"
        },
        {
            "name": "hero_main_text",
            "label": "Hero Main Text",
            "type": "text",
            "default": "FOOD",
            "row": 1,
            "colClass": "col-md-6"
        },
        {
            "name": "intro_text",
            "label": "Intro Text",
            "type": "richtext",
            "default": "Delicious <span class=\"hl\">Fast Food</span><br/>for Every Moment",
            "tip": "use <br> tags to break",
            "row": 2,
            "colClass": "col-12"
        },
        {
            "name": "intro_paragraph",
            "label": "Intro paragraph",
            "type": "textarea",
            "default": "Experience bold flavors crafted from premium ingredients. From crispy burgers to gourmet pizzas - every bite is an adventure worth savoring.",
            "row": 3,
            "colClass": "col-12"
        },
        {
            "name": "youtube_link",
            "label": "Youtube Link",
            "type": "url",
            "default": {
                "url": "https://www.youtube.com/watch?v=RXv_uIN6e-Y",
                "class": "magnific_popup btn-play popup-youtube",
                "id": "",
                "target": ""
            },
            "row": 4,
            "colClass": "col-md-6"
        },
        {
            "name": "story_text",
            "label": "Story Text",
            "type": "text",
            "default": "Watch Our Story",
            "row": 4,
            "colClass": "col-md-6"
        },
        {
            "name": "hero_image",
            "label": "Hero Image 3",
            "type": "media_selector",
            "default": {
                "url": "",
                "alt": "",
                "class": ""
            },
            "row": 5,
            "colClass": "col-12"
        },
        {
            "name": "stats",
            "label": "Stats",
            "type": "repeater",
            "item": "stat",
            "row": 6,
            "colClass": "col-12",
            "fields": [
                {
                    "name": "stat_number",
                    "label": "Stat Number",
                    "type": "text",
                    "default": "850",
                    "row": 1,
                    "colClass": "col-md-3"
                }
            ]
        },
        {
            "name": "facts",
            "label": "Facts",
            "type": "repeater",
            "item": "fact",
            "row": 7,
            "colClass": "col-12",
            "fields": [
                {
                    "name": "text",
                    "label": "Text",
                    "type": "text",
                    "default": "Hot Deal",
                    "row": 1,
                    "colClass": "col-md-6"
                },
                {
                    "name": "deal_name",
                    "label": "Deal Name",
                    "type": "text",
                    "default": "30% off today",
                    "row": 1,
                    "colClass": "col-md-6"
                }
            ]
        }
    ],
    "config": {
        "background_color": "#ffffff",
        "margin_top": "0",
        "margin_bottom": "0",
        "padding_top": "0",
        "padding_bottom": "0",
        "max_width": "100%"
    },
    "updated_at": "2026-06-30T10:38:01+00:00"
}
--}}

@verbatim
<section id="hero">
   <div class="hs hs1"></div>
   <div class="hs hs2"></div>
   <div class="hbgtxt">{{ $blockData['hero_main_text'] }}</div>
   <div class="container">
      <div class="row align-items-center g-5" style="min-height:88vh;">
         <div class="col-lg-6">
            <div class="hbadge">
               <div class="hbi"><i class="fas fa-star"></i></div>
               <span>{{ $blockData['hero_header'] }}</span>
            </div>
            <h1 class="htitle">{{ $blockData['intro_text'] }}</h1>
            <p class="hdesc">{{ $blockData['intro_paragraph'] }}</p>
            <div class="d-flex flex-wrap gap-3 mb-2">
               <a href="#menu" class="btn-red"><i class="fas fa-utensils"></i>Explore Menu</a>
               <a href="{{ $blockData['youtube_link']['url'] }}"
                  class="{{ $blockData['youtube_link']['class'] }}">
                  <div class="pico"><i class="fas fa-play"></i></div>
                  <span>{{ $blockData['story_text'] }}</span>
               </a>
            </div>
            @foreach ($blockData['stats'] ?? [] as $stat)
            <div class="hstats d-flex gap-3 flex-wrap mt-4">
               <div class="hstat"><span class="snum">{{ $stat['stat_number'] }}<em>{{ $stat['stat_operator'] }}</em></span><small>{{ $stat['stat_text'] }}</small></div>
               <div class="sdiv"></div>
            </div>
            @endforeach
         </div>
         <div class="col-lg-6">
            <div style="position:relative;text-align:center;">
               <div class="hcircle">
                  <img src="{{ $blockData['hero_image']['url'] }}" alt="{{ $blockData['hero_image']['alt'] }}" class="{{ $blockData['hero_image']['class'] }}" />
               </div>
               @foreach ($blockData['facts'] ?? [] as $fact)
               <div class="fcard fc1">
                  <div class="fcoi r"><i class="fas fa-fire"></i></div>
                  <div><span class="fcnum">{{ $fact['text'] }}</span><span class="fcsm">{{ $fact['deal_name'] }}</span></div>
               </div>
               @endforeach
            </div>
         </div>
      </div>
   </div>
</section>
@endverbatim
