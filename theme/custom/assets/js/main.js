function byId(id) {
    return document.getElementById(id);
}

if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 680,
        once: true,
        offset: 55
    });
}

/* NAVBAR SCROLL & ACTIVE LINK  */
var nav = byId('nav');
var btt = byId('btt');

if (nav || btt) {
    window.addEventListener('scroll', function() {
        if (nav) {
            nav.classList.toggle('scrolled', window.scrollY > 60);
        }
        if (btt) {
            btt.classList.toggle('show', window.scrollY > 300);
        }
        if (! nav) {
            return;
        }
        document.querySelectorAll('section[id]').forEach(function(sec) {
            var top = sec.offsetTop - 110,
                bot = top + sec.offsetHeight;
            if (window.scrollY >= top && window.scrollY < bot) {
                document.querySelectorAll('.nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                var lnk = document.querySelector('.nav-link[href="#' + sec.id + '"]');
                if (lnk) lnk.classList.add('active');
            }
        });
    });
}

/*  SMOOTH SCROLL + MOBILE NAV CLOSE  */
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var href = this.getAttribute('href');
        if (href === '#') return;
        var t = document.querySelector(href);
        if (t) {
            e.preventDefault();
            var navCollapse = byId('navmenu');
            if (navCollapse && navCollapse.classList.contains('show')) {
                var bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    navCollapse.classList.remove('show');
                }
            }
            setTimeout(function() {
                window.scrollTo({
                    top: t.offsetTop - 78,
                    behavior: 'smooth'
                });
            }, 50);
        }
    });
});

var searchOv = byId('searchOv');
var navSearchBtn = byId('navSearchBtn');
var searchClose = byId('searchClose');
var searchInput = byId('searchInput');

function closeSearch() {
    if (! searchOv) {
        return;
    }
    searchOv.classList.remove('open');
    document.body.style.overflow = '';
}

if (searchOv && navSearchBtn && searchClose) {
    navSearchBtn.addEventListener('click', function() {
        searchOv.classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            if (searchInput) {
                searchInput.focus();
            }
        }, 220);
    });

    searchClose.addEventListener('click', closeSearch);

    searchOv.addEventListener('click', function(e) {
        if (e.target === searchOv) closeSearch();
    });
}

document.querySelectorAll('.sovcat').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.sovcat').forEach(function(b) {
            b.classList.remove('active');
        });
        this.classList.add('active');
        var f = this.getAttribute('data-cat');
        closeSearch();
        setTimeout(function() {
            filterMenu(f);
            var menu = byId('menu');
            if (menu) {
                menu.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 300);
    });
});

document.querySelectorAll('.sovtrend .ttag').forEach(function(t) {
    t.addEventListener('click', function() {
        if (! searchInput) {
            return;
        }
        searchInput.value = this.textContent.trim();
        searchInput.focus();
    });
});

$(document).ready(function() {
    $('.magnific_popup').magnificPopup({
        disableOn: 700,
        type: 'iframe',
        mainClass: 'mfp-fade',
        removalDelay: 160,
        preloader: false,
        fixedContentPos: false,
        disableOn: 300
    });
});

function filterMenu(cat) {
    document.querySelectorAll('.filtbtn').forEach(function(b) {
        b.classList.toggle('active', b.getAttribute('data-f') === cat);
    });
    document.querySelectorAll('.catcard').forEach(function(c) {
        c.classList.toggle('active', c.getAttribute('data-filter') === cat);
    });
    document.querySelectorAll('.mwrap').forEach(function(w) {
        var c = w.getAttribute('data-c');
        if (cat === 'all' || c === cat) {
            w.classList.remove('gone');
            w.style.opacity = '0';
            w.style.transform = 'translateY(16px)';
            setTimeout(function() {
                w.style.transition = 'opacity .38s,transform .38s';
                w.style.opacity = '1';
                w.style.transform = 'translateY(0)';
            }, 60);
        } else {
            w.classList.add('gone');
        }
    });
}

document.querySelectorAll('.filtbtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        filterMenu(this.getAttribute('data-f'));
    });
});

document.querySelectorAll('.catcard').forEach(function(card) {
    card.addEventListener('click', function() {
        var f = this.getAttribute('data-filter');
        var menu = byId('menu');
        if (menu) {
            window.scrollTo({
                top: menu.offsetTop - 80,
                behavior: 'smooth'
            });
        }
        setTimeout(function() {
            filterMenu(f);
        }, 480);
    });
});

var menuPop = byId('menuPop');
var mpQty = 1;

function openMenuPop(card) {
    if (! menuPop) {
        return;
    }

    var img = card.getAttribute('data-img');
    var title = card.getAttribute('data-title');
    var cat = card.getAttribute('data-cat');
    var price = card.getAttribute('data-price');
    var old = card.getAttribute('data-old');
    var rating = parseFloat(card.getAttribute('data-rating'));
    var reviews = card.getAttribute('data-reviews');
    var cal = card.getAttribute('data-cal');
    var time = card.getAttribute('data-time');
    var desc = card.getAttribute('data-desc');
    var tags = card.getAttribute('data-tags') || '';

    byId('mpImg').setAttribute('src', img);
    byId('mpCat').textContent = cat;
    byId('mpTitle').textContent = title;

    var full = Math.round(rating),
        empty = 5 - full;
    byId('mpStars').innerHTML =
        '<i class="fas fa-star"></i>'.repeat(full) + '☆'.repeat(empty) +
        ' <span style="color:#bbb;font-size:.78rem;">' + rating + ' (' + reviews + ' reviews)</span>';

    byId('mpDesc').textContent = desc;

    byId('mpPrice').innerHTML =
        price + (old ? '<small style="color:#ccc;text-decoration:line-through;margin-left:8px;font-size:1rem;">' + old + '</small>' : '');

    byId('mpMeta').innerHTML =
        '<div class="mpm"><div class="mpmv">' + cal + ' kcal</div><div class="mpml">Calories</div></div>' +
        '<div class="mpm"><div class="mpmv">' + time + ' min</div><div class="mpml">Prep Time</div></div>' +
        '<div class="mpm"><div class="mpmv">' + rating + '/5</div><div class="mpml">Rating</div></div>';

    byId('mpTags').innerHTML =
        tags.split(',').filter(Boolean).map(function(t) {
            return '<span class="mptag">' + t.trim() + '</span>';
        }).join('');

    mpQty = 1;
    byId('mpQnum').textContent = 1;
    byId('mpAddCart').innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
    byId('mpAddCart').style.background = '';

    menuPop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

document.querySelectorAll('.mcard').forEach(function(card) {
    card.addEventListener('click', function() {
        openMenuPop(this);
    });
});

document.querySelectorAll('.madd').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        openMenuPop(this.closest('.mcard'));
    });
});

document.querySelectorAll('.mhrt').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var ico = this.querySelector('i');
        ico.classList.toggle('far');
        ico.classList.toggle('fas');
        this.style.color = ico.classList.contains('fas') ? 'var(--primary)' : '#ccc';
    });
});

if (menuPop) {
    var mpClose = byId('mpClose');
    if (mpClose) {
        mpClose.addEventListener('click', closeMenuPop);
    }
    menuPop.addEventListener('click', function(e) {
        if (e.target === this) closeMenuPop();
    });

    var mpPlus = byId('mpPlus');
    if (mpPlus) {
        mpPlus.addEventListener('click', function() {
            byId('mpQnum').textContent = ++mpQty;
        });
    }

    var mpMinus = byId('mpMinus');
    if (mpMinus) {
        mpMinus.addEventListener('click', function() {
            if (mpQty > 1) byId('mpQnum').textContent = --mpQty;
        });
    }

    var mpAddCart = byId('mpAddCart');
    if (mpAddCart) {
        mpAddCart.addEventListener('click', function() {
            var cartCount = byId('cartCount');
            var cnt = parseInt(cartCount ? cartCount.textContent : '0', 10) + mpQty;
            if (cartCount) {
                cartCount.textContent = cnt;
            }
            this.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
            this.style.background = 'linear-gradient(135deg,var(--green),#1a4a35)';
            var self = this;
            setTimeout(function() {
                closeMenuPop();
                self.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                self.style.background = '';
            }, 1000);
        });
    }
}

function closeMenuPop() {
    if (! menuPop) {
        return;
    }
    menuPop.classList.remove('open');
    document.body.style.overflow = '';
}

var resBtn = byId('resBtn');
if (resBtn) {
    resBtn.addEventListener('click', function() {
        var btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';
        btn.disabled = true;
        setTimeout(function() {
            btn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Reservation';
            btn.disabled = false;
            var ok = byId('resOk');
            if (ok) {
                ok.style.display = 'block';
                ok.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }, 1500);
    });
}

var ctcBtn = byId('ctcBtn');
if (ctcBtn) {
    ctcBtn.addEventListener('click', function() {
        var btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
        setTimeout(function() {
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
            btn.disabled = false;
            var ok = byId('ctcOk');
            if (ok) {
                ok.style.display = 'block';
                ok.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }, 1500);
    });
}

var galPop = byId('galPop');
var galData = [];
var galIdx = 0;

document.querySelectorAll('.gitem').forEach(function(item) {
    galData.push({
        img: item.getAttribute('data-gimg'),
        title: item.getAttribute('data-gtitle'),
        desc: item.getAttribute('data-gdesc')
    });
    item.addEventListener('click', function() {
        openGal(parseInt(this.getAttribute('data-gi')));
    });
});

function openGal(i) {
    if (! galPop) {
        return;
    }
    galIdx = i;
    var g = galData[i];
    byId('gpImg').setAttribute('src', g.img);
    byId('gpTitle').textContent = g.title;
    byId('gpDesc').innerHTML = g.desc;
    galPop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

if (galPop) {
    var gpClose = byId('gpClose');
    if (gpClose) {
        gpClose.addEventListener('click', closeGal);
    }
    galPop.addEventListener('click', function(e) {
        if (e.target === this) closeGal();
    });

    var gpPrev = byId('gpPrev');
    if (gpPrev) {
        gpPrev.addEventListener('click', function() {
            openGal((galIdx - 1 + galData.length) % galData.length);
        });
    }

    var gpNext = byId('gpNext');
    if (gpNext) {
        gpNext.addEventListener('click', function() {
            openGal((galIdx + 1) % galData.length);
        });
    }
}

function closeGal() {
    if (! galPop) {
        return;
    }
    galPop.classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSearch();
        closeMenuPop();
        closeGal();
        if (typeof $.magnificPopup !== 'undefined') $.magnificPopup.close();
    }
});

if (document.querySelector('.tesSwiper')) {
    new Swiper('.tesSwiper', {
        slidesPerView: 1,
        spaceBetween: 22,
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true
        },
        breakpoints: {
            640: {
                slidesPerView: 2
            },
            1024: {
                slidesPerView: 3
            }
        }
    });
}

var cdH = byId('cdH');
var cdM = byId('cdM');
var cdS = byId('cdS');

if (cdH && cdM && cdS) {
    var cH = 8,
        cM = 45,
        cS = 30;
    setInterval(function() {
        cS--;
        if (cS < 0) {
            cS = 59;
            cM--;
        }
        if (cM < 0) {
            cM = 59;
            cH--;
        }
        if (cH < 0) {
            cH = 8;
            cM = 45;
            cS = 30;
        }
        cdH.textContent = String(cH).padStart(2, '0');
        cdM.textContent = String(cM).padStart(2, '0');
        cdS.textContent = String(cS).padStart(2, '0');
    }, 1000);
}

var nlBtn = byId('nlBtn');
var nlEmail = byId('nlEmail');
if (nlBtn && nlEmail) {
    nlBtn.addEventListener('click', function() {
        var email = nlEmail.value;
        if (email && email.includes('@')) {
            var btn = this;
            btn.textContent = '✓ Subscribed!';
            btn.style.background = '#4ade80';
            btn.style.color = '#222';
            nlEmail.value = '';
            setTimeout(function() {
                btn.textContent = 'Subscribe';
                btn.style.background = '';
                btn.style.color = '';
            }, 3000);
        }
    });
}

var numAnimated = false;
window.addEventListener('scroll', function() {
    var hero = byId('hero');
    if (!numAnimated && hero && window.scrollY > hero.offsetHeight - 300) {
        numAnimated = true;
        document.querySelectorAll('.snum').forEach(function(el) {
            var txt = el.textContent;
            var num = parseInt(txt);
            var suf = txt.replace(/[0-9]/g, '');
            if (isNaN(num)) return;
            var start = 0;
            var step = Math.ceil(num / 55);
            var iv = setInterval(function() {
                start += step;
                if (start >= num) {
                    start = num;
                    clearInterval(iv);
                }
                el.textContent = start + suf;
            }, 1400 / 55);
        });
    }
});
