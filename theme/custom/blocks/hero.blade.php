{{-- loom:meta
{
    "name": "Hero",
    "slug": "hero",
    "parameters": [
        {
            "name": "hero_header",
            "label": "Hero Header",
            "type": "text",
            "default": "#1 Rated Fast Food Restaurant in New York"
        },
        {
            "name": "stats",
            "label": "Stats",
            "type": "repeater",
            "item": "stat",
            "fields": [
                {
                    "name": "stat_number",
                    "label": "Stat Number",
                    "type": "text",
                    "default": "850"
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
    "updated_at": "2026-06-29T16:06:09+00:00"
}
--}}

@verbatim
<section id="hero">
         <div class="hs hs1"></div>
         <div class="hs hs2"></div>
         <div class="hbgtxt">FOOD</div>
         <div class="container">
            <div class="row align-items-center g-5" style="min-height:88vh;">
               <div class="col-lg-6">
                  <div class="hbadge">
                     <div class="hbi"><i class="fas fa-star"></i></div>
                     <span>{{ $blockData['hero_header'] }}</span>
                  </div>
                  <h1 class="htitle">Delicious <span class="hl">Fast Food</span><br/>for Every Moment</h1>
                  <p class="hdesc">Experience bold flavors crafted from premium ingredients. From crispy burgers to gourmet pizzas - every bite is an adventure worth savoring.</p>
                  <div class="d-flex flex-wrap gap-3 mb-2">
                     <a href="#menu" class="btn-red"><i class="fas fa-utensils"></i>Explore Menu</a>
                     <!-- FIX 2: Magnific popup video trigger -->
					 <a href="https://www.youtube.com/watch?v=RXv_uIN6e-Y" class="magnific_popup btn-play popup-youtube">
						<div class="pico"><i class="fas fa-play"></i></div>
						<span>Watch Our Story</span>
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
                        <img src="img/banner-img.jpg" alt="Burger"/>
                     </div>
                     <div class="fcard fc1">
                        <div class="fcoi r"><i class="fas fa-fire"></i></div>
                        <div><span class="fcnum">Hot Deal</span><span class="fcsm">30% off today</span></div>
                     </div>
                     <div class="fcard fc2">
                        <div class="fcoi y"><i class="fas fa-star"></i></div>
                        <div><span class="fcnum">4.9/5</span><span class="fcsm">2k+ reviews</span></div>
                     </div>
                     <div class="fcard fc3">
                        <div class="fcoi g"><i class="fas fa-clock"></i></div>
                        <div><span class="fcnum">20 min</span><span class="fcsm">Fast delivery</span></div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
@endverbatim
