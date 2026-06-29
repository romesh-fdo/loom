{{-- loom:meta
{
    "name": "Header",
    "slug": "header",
    "slot": "header",
    "enabled": true,
    "parameters": [],
    "values": [],
    "updated_at": "2026-06-29T12:00:00+00:00"
}
--}}

@verbatim
<nav class="navbar navbar-expand-lg" id="nav">
  <div class="container">
    <a class="navbar-brand" href="#">
      <div class="blogo">
        <div class="bico"><i class="fas fa-utensils"></i></div>
        <div>
          <div class="bname">Sar<span>ab</span></div>
          <div class="bsub">Fast Food & Restaurant</div>
        </div>
      </div>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <i class="fas fa-bars" style="color:var(--primary);font-size:1.35rem;"></i>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link active" href="#hero">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
      </ul>
    </div>
  </div>
</nav>
@endverbatim
