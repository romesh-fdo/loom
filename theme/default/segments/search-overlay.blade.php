{{-- loom:meta
{
    "name": "Search overlay",
    "slug": "search-overlay",
    "slot": "search_overlay",
    "enabled": true,
    "parameters": [],
    "updated_at": "2026-06-29T12:00:00+00:00"
}
--}}

@verbatim
<div id="searchOv">
  <button class="sovclose" id="searchClose"><i class="fas fa-times"></i></button>
  <div class="sovbox">
    <h4>What are you craving today?</h4>
    <div class="sovinput">
      <input type="text" id="searchInput" placeholder="Search burgers, pizza, chicken..." autocomplete="off"/>
      <button><i class="fas fa-search"></i></button>
    </div>
  </div>
</div>
@endverbatim
