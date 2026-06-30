{{-- loom:meta
{
    "name": "Header",
    "slug": "header",
    "slot": "header",
    "enabled": true,
    "parameters": [],
    "updated_at": "2026-06-30T16:00:00+00:00"
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
        <li class="nav-item"><a class="nav-link" href="#chefs">Chefs</a></li>
        <li class="nav-item"><a class="nav-link" href="#reservation">Reservation</a></li>
        <li class="nav-item"><a class="nav-link" href="#testimonials">Reviews</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact-section">Contact</a></li>
      </ul>
      <div class="d-flex align-items-center gap-1">
        <button id="navSearchBtn" title="Search"><i class="fas fa-search"></i></button>
        <a href="#menu" class="nav-link nav-cta"><i class="fas fa-shopping-bag me-1"></i>Order Now</a>
      </div>
    </div>
  </div>
</nav>
@endverbatim
