(function () {
  if (typeof window === 'undefined') return;
  function readBool(v, def) {
    if (v === undefined || v === null || v === '') return !!def;
    if (typeof v === 'boolean') return v;
    var s = String(v).toLowerCase();
    if (s === 'true' || s === '1' || s === 'yes') return true;
    if (s === 'false' || s === '0' || s === 'no') return false;
    return !!def;
  }
  function readNum(v, def) {
    var n = parseFloat(v);
    return isNaN(n) ? (def == null ? 0 : def) : n;
  }
  function initSwipers() {
    if (typeof Swiper === 'undefined') return;
    document.querySelectorAll('.mgsc-swiper').forEach(function (el) {
      if (el.__mgsc_inited) return;
      el.__mgsc_inited = true;
      // eslint-disable-next-line no-new
      new Swiper(el, {
        slidesPerView: 1,
        spaceBetween: 12,
        loop: false,
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
        pagination: {
          el: el.querySelector('.swiper-pagination'),
          clickable: true,
        },
        breakpoints: {
          480: { slidesPerView: 2 },
          768: { slidesPerView: 3 },
          1024: { slidesPerView: 4 },
        },
        on: {
          init: function(){
            if (autoplayEnabled) {
              try { if (this.autoplay && typeof this.autoplay.start === 'function') this.autoplay.start(); } catch(e){}
            }
          }
        }
      });
    });

    // Initialize slider-cards (product_slider.tpl)
    document.querySelectorAll('.slider-cards').forEach(function (el) {
      if (el.__mgsc_inited) return;
      var ds = el.dataset || {};
      var wrapper = el.querySelector('.swiper-wrapper');
      var slides = el.querySelectorAll('.swiper-slide');
      var baseSpv = readNum(ds.slidesPerView, 1);
      // Duplicate slides to meet at least baseSpv (so multiple items show as per BO config)
      if (wrapper && slides.length > 0 && slides.length < baseSpv) {
        var needed = baseSpv - slides.length;
        for (var i = 0; i < needed; i++) {
          var clone = slides[i % slides.length].cloneNode(true);
          wrapper.appendChild(clone);
        }
        slides = el.querySelectorAll('.swiper-slide');
      }
      // If still only one slide, treat as single-slide (no nav)
      if (slides.length <= 1) {
        el.__mgsc_inited = true;
        el.classList.add('single-slide');
        return;
      }
      el.__mgsc_inited = true;
      var baseSpv = readNum(ds.slidesPerView, 1);
      var spaceBetween = readNum(ds.spaceBetween, 20);
      var loop = readBool(ds.loop, false);
      var speed = readNum(ds.speed, 600);
      var autoHeight = readBool(ds.autoHeight, false);
      var autoplayEnabled = readBool(ds.autoplayEnabled, false);
      var autoplay = autoplayEnabled ? { delay: 3000, disableOnInteraction: false } : false;
      var centerBase = readBool(ds.centerEnabled, false);
      var bp = {
        480: { slidesPerView: readNum(ds.slidesPerViewSm, baseSpv), centeredSlides: readBool(ds.centerEnabledSm, centerBase) },
        768: { slidesPerView: readNum(ds.slidesPerViewMd, baseSpv), centeredSlides: readBool(ds.centerEnabledMd, centerBase) },
        992: { slidesPerView: readNum(ds.slidesPerViewLg, baseSpv), centeredSlides: readBool(ds.centerEnabledLg, centerBase) },
        1200: { slidesPerView: readNum(ds.slidesPerViewXl, baseSpv), centeredSlides: readBool(ds.centerEnabledXl, centerBase) }
      };
      // eslint-disable-next-line no-new
      el.classList.remove('mgsc-fallback');
      var delay = autoplayEnabled ? 3000 : 0;
      el.classList.add('mgsc-mode-swiper');
      var swiper = new Swiper(el, {
        slidesPerView: baseSpv,
        spaceBetween: spaceBetween,
        loop: loop,
        speed: speed,
        autoHeight: autoHeight,
        autoplay: autoplay,
        centeredSlides: centerBase,
        watchOverflow: false,
        wrapperClass: 'swiper-wrapper',
        slideClass: 'swiper-slide',
        navigation: {
          nextEl: el.querySelector('.swiper-navigation .next'),
          prevEl: el.querySelector('.swiper-navigation .prev'),
        },
        breakpoints: bp,
      });
      // Ensure autoplay even if Autoplay module not present
      el.__mg_swiper = swiper;
      // Debug badge: set mode label
      var dbg1 = el.querySelector('.mgsc-debug__mode');
      if (dbg1) { try { dbg1.textContent = 'Swiper'; } catch(e){} }
      var manualTimer = null;
      function startManual(){
        if (!autoplayEnabled) return;
        if (swiper.autoplay && typeof swiper.autoplay.start === 'function') { try { swiper.autoplay.start(); } catch(e){} return; }
        stopManual();
        manualTimer = setInterval(function(){ if (swiper && typeof swiper.slideNext === 'function') swiper.slideNext(); }, Math.max(2000, delay));
      }
      function stopManual(){ if (manualTimer) { clearInterval(manualTimer); manualTimer = null; } }
      el.addEventListener('mouseenter', stopManual);
      el.addEventListener('mouseleave', startManual);
      startManual();
    });
  }
  // Basic fallback if Swiper (CDN) is unavailable: translate wrapper by 100% per slide
  function initFallbackSliders() {
    document.querySelectorAll('.mgsc-swiper').forEach(function (el) {
      if (el.__mgsc_inited || el.__mgsc_fallback) return;
      var wrapper = el.querySelector('.swiper-wrapper');
      var slides = el.querySelectorAll('.swiper-slide');
      if (!wrapper || !slides.length) return;
      el.__mgsc_fallback = true;
      // Debug badge: set mode label to Fallback
      var dbg2 = el.querySelector('.mgsc-debug__mode');
      if (dbg2) { try { dbg2.textContent = 'Fallback'; } catch(e){} }
      var idx = 0;
      function clamp(i){ return Math.max(0, Math.min(i, slides.length - 1)); }
      function update(){
        wrapper.style.transition = 'transform 300ms';
        wrapper.style.transform = 'translate3d(' + (-idx * 100) + '%,0,0)';
      }
      var prev = el.querySelector('.swiper-button-prev');
      var next = el.querySelector('.swiper-button-next');
      if (prev) prev.addEventListener('click', function(){ idx = clamp(idx - 1); update(); });
      if (next) next.addEventListener('click', function(){ idx = clamp(idx + 1); update(); });
      // Simple pagination bullets if present
      var pag = el.querySelector('.swiper-pagination');
      if (pag) {
        pag.innerHTML = '';
        for (var i = 0; i < slides.length; i++) {
          var b = document.createElement('span');
          b.className = 'swiper-pagination-bullet' + (i === 0 ? ' swiper-pagination-bullet-active' : '');
          (function(i2){ b.addEventListener('click', function(){ idx = i2; update(); setActive(); }); })(i);
          pag.appendChild(b);
        }
        function setActive(){
          var bullets = pag.querySelectorAll('.swiper-pagination-bullet');
          bullets.forEach(function(bb, j){ if (j === idx) bb.classList.add('swiper-pagination-bullet-active'); else bb.classList.remove('swiper-pagination-bullet-active'); });
        }
      }
      // Initialize position
      update();
      window.addEventListener('resize', update);
    });

    // No SPV helpers: widths are controlled by CSS classes; JS measures DOM sizes

    // Fallback for slider-cards
    document.querySelectorAll('.slider-cards').forEach(function (el) {
      if (el.__mgsc_inited || el.__mgsc_fallback) return;
      var wrapper = el.querySelector('.swiper-wrapper');
      var slides = el.querySelectorAll('.swiper-slide');
      if (!wrapper || !slides.length) return;
      var ds = el.dataset || {};
      el.classList.add('mgsc-fallback');
      var autoplay = (ds.autoplayEnabled === 'true');
      var loop = (ds.loop === 'true');
      var transitionMs = parseInt(ds.speed || '600', 10);
      if (!isFinite(transitionMs) || transitionMs < 0) transitionMs = 600;
      var autoIntervalMs = Math.max(2000, transitionMs + 2000);
      if (slides.length <= 1) {
        el.__mgsc_inited = true; // treat as initialized to avoid later attempts
        el.classList.add('single-slide');
        return;
      }
      el.__mgsc_fallback = true;
      var idx = 0;
      function visibleCount(){
        if (!slides.length) return 1;
        var slideW = slides[0].offsetWidth || 1;
        var contW = el.clientWidth || slideW;
        return Math.max(1, Math.round(contW / slideW));
      }
      function clamp(i){
        var spv = visibleCount();
        var maxIdx = Math.max(0, (slides.length - spv));
        return Math.max(0, Math.min(i, maxIdx));
      }
      function update(){
        var spv = visibleCount();
        if (idx > (slides.length - spv)) { idx = clamp(idx); }
        wrapper.style.transition = 'transform ' + transitionMs + 'ms';
        var step = (slides[0].offsetWidth / (el.clientWidth || 1)) * 100;
        wrapper.style.transform = 'translate3d(' + (-(idx) * step) + '%,0,0)';
      }
      function goNext(){
        var spv = visibleCount();
        var maxIdx = Math.max(0, (slides.length - spv));
        if (loop) {
          idx = (idx >= maxIdx) ? 0 : (idx + 1);
        } else {
          idx = clamp(idx + 1);
        }
        update();
      }
      function goPrev(){
        var spv = visibleCount();
        var maxIdx = Math.max(0, (slides.length - spv));
        if (loop) {
          idx = (idx <= 0) ? maxIdx : (idx - 1);
        } else {
          idx = clamp(idx - 1);
        }
        update();
      }
      var prevBtn = el.querySelector('.swiper-navigation .prev');
      var nextBtn = el.querySelector('.swiper-navigation .next');
      if (prevBtn) prevBtn.addEventListener('click', function(){ stopAuto(); goPrev(); startAuto(); });
      if (nextBtn) nextBtn.addEventListener('click', function(){ stopAuto(); goNext(); startAuto(); });
      var autoTimer = null;
      function startAuto(){ if (!autoplay) return; stopAuto(); autoTimer = setInterval(goNext, autoIntervalMs); }
      function stopAuto(){ if (autoTimer) { clearInterval(autoTimer); autoTimer = null; } }
      el.addEventListener('mouseenter', stopAuto);
      el.addEventListener('mouseleave', startAuto);
      update();
      startAuto();
      window.addEventListener('resize', function(){ idx = clamp(idx); update(); startAuto(); });
    });
  }
  function ensureSwiperThenInit() {
    if (typeof Swiper !== 'undefined') {
      initSwipers();
      return;
    }
    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      if (typeof Swiper !== 'undefined') {
        clearInterval(timer);
        initSwipers();
      } else if (tries > 50) { // ~10s total wait (50 * 200ms)
        clearInterval(timer);
        // Give up: do not initialize JS fallback for slider-cards; keep layout static.
        document.querySelectorAll('.slider-cards').forEach(function (el) {
          if (el.__mgsc_inited) return;
          var dbg = el.querySelector('.mgsc-debug__mode');
          if (dbg) { try { dbg.textContent = 'No Swiper'; } catch (e) {} }
          el.__mgsc_inited = true;
        });
        // Intentionally skip initFallbackSliders() to enforce Swiper-only behavior.
      }
    }, 200);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureSwiperThenInit);
  } else {
    ensureSwiperThenInit();
  }
  window.addEventListener('load', ensureSwiperThenInit);
})();
