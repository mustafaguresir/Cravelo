/*!
 * Cravelo Burger Hero Scrollytelling Animation Controller
 * Standard GSAP ScrollTrigger "Layer Animation" Pattern:
 * pin: '#burger-sticky-viewport', pinSpacing: false, scrub: 1, start: 'top top', end: 'bottom bottom'.
 * Initial resting state: only Layer 1 is visible; Layers 2-7 suspended above.
 * Smooth sequential drops with hardware acceleration and 0px display:none recovery.
 */
(function (global) {
  'use strict';

  var MICROCOPY_STEPS = [
    { threshold: 0.00, title: 'Taze Brioche Ekmeği', subtitle: 'Tereyağlı, altın sarısı fırın bazı' },
    { threshold: 0.15, title: 'Çıtır Taze Kıvırcık Marul', subtitle: 'Dalından sofraya ferah lezzet' },
    { threshold: 0.30, title: '%100 Dana Antrikot Köfte', subtitle: 'Alevde ızgara, sulu ve leziz' },
    { threshold: 0.45, title: 'Erimiş Cheddar', subtitle: 'Sıcak köftenin üstünde eriyen nefis peynir' },
    { threshold: 0.60, title: 'Taze Tarla Domatesleri', subtitle: 'Kalın dilim sulu tarla domatesi' },
    { threshold: 0.75, title: 'Karamelize Soğan & Kornişon Turşu', subtitle: 'Özel marinasyonlu tatlı ve ekşi denge' },
    { threshold: 0.90, title: 'Taze Brioche Ekmeği', subtitle: '40+ kavrulmuş susamlı parlak brioche tacı' },
    { threshold: 0.98, title: 'Sıcak ve Hızlı Kapında', subtitle: 'Cravelo akıllı kurye filosuyla dakikalar içinde' }
  ];

  function getStepForProgress(p) {
    if (typeof p !== 'number' || isNaN(p) || p <= 0) {
      return MICROCOPY_STEPS[0];
    }
    var step = MICROCOPY_STEPS[0];
    for (var i = 0; i < MICROCOPY_STEPS.length; i++) {
      if (p >= MICROCOPY_STEPS[i].threshold) {
        step = MICROCOPY_STEPS[i];
      }
    }
    return step;
  }

  function refresh() {
    if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
      ScrollTrigger.refresh();
    }
  }

  var activeTimeline = null;

  function initBurgerScrollytelling() {
    if (typeof document === 'undefined') return null;

    var container = document.getElementById('burger-scrolly-container');
    var viewport = document.getElementById('burger-sticky-viewport');
    if (!container || !viewport) return null;

    if (typeof gsap === 'undefined') {
      if (typeof console !== 'undefined' && console.warn) {
        console.warn('GSAP is not loaded. Scrollytelling will not initialize.');
      }
      return null;
    }

    if (typeof ScrollTrigger !== 'undefined' && gsap.registerPlugin) {
      gsap.registerPlugin(ScrollTrigger);
    }

    // Kill any existing timeline to prevent duplicate trigger bindings
    if (activeTimeline) {
      if (activeTimeline.scrollTrigger) {
        activeTimeline.scrollTrigger.kill();
      }
      activeTimeline.kill();
      activeTimeline = null;
    }

    var badgeTitle = document.getElementById('burger-badge-title');
    var badgeSubtitle = document.getElementById('burger-badge-subtitle');
    var progressBar = document.getElementById('burger-scroll-progress-bar');
    var steamEl = document.getElementById('burger-steam');
    var ctaContainer = document.getElementById('burger-final-cta-container');
    var scrollHint = viewport.querySelector('.burger-scroll-hint');

    // Initial resting state: only Layer 1 visible at y:0, opacity:1; Layers 2-7 suspended above
    gsap.set('[data-layer="1"]', { y: 0, opacity: 1, scale: 1 });
    gsap.set('[data-layer="2"]', { y: -220, rotate: -5, opacity: 0 });
    gsap.set('[data-layer="3"]', { y: -250, scale: 1.06, opacity: 0 });
    gsap.set('[data-layer="4"]', { y: -260, opacity: 0 });
    gsap.set('[data-layer="5"]', { y: -270, rotate: 4, opacity: 0 });
    gsap.set('[data-layer="6"]', { y: -280, opacity: 0 });
    gsap.set('[data-layer="7"]', { y: -380, scaleY: 1.0, opacity: 0 });
    if (steamEl) gsap.set(steamEl, { opacity: 0, y: 15 });
    if (ctaContainer) gsap.set(ctaContainer, { opacity: 0, y: 35 });

    // Standard GSAP ScrollTrigger "Layer Animation" Pattern
    var tl = gsap.timeline({
      scrollTrigger: {
        trigger: '#burger-scrolly-container',
        pin: '#burger-sticky-viewport',
        pinSpacing: false,
        start: 'top top',
        end: 'bottom bottom',
        scrub: 1,
        onUpdate: function (self) {
          var p = self.progress;
          if (progressBar) {
            progressBar.style.width = (p * 100).toFixed(1) + '%';
          }
          var step = getStepForProgress(p);
          if (badgeTitle && badgeTitle.textContent !== step.title) {
            badgeTitle.textContent = step.title;
          }
          if (badgeSubtitle && badgeSubtitle.textContent !== step.subtitle) {
            badgeSubtitle.textContent = step.subtitle;
          }
          if (scrollHint) {
            scrollHint.style.opacity = p > 0.80 ? '0' : '';
          }
        }
      }
    });

    // 0% -> 15%: Layer 2 (Crisp lettuce) drops smoothly from above
    tl.fromTo('[data-layer="2"]',
      { y: -220, rotate: -5, opacity: 0 },
      { y: 0, rotate: 0, opacity: 1, duration: 15, ease: 'power2.out' },
      0
    );

    // 15% -> 30%: Layer 3 (Flame-grilled beef patty) drops into place
    tl.fromTo('[data-layer="3"]',
      { y: -250, scale: 1.06, opacity: 0 },
      { y: 0, scale: 1.0, opacity: 1, duration: 15, ease: 'power2.out' },
      15
    );

    // 25% -> 35%: Steam wisps start rising above hot patty
    if (steamEl) {
      tl.fromTo(steamEl,
        { opacity: 0, y: 15 },
        { opacity: 0.85, y: 0, duration: 10, ease: 'power1.out' },
        25
      );
    }

    // 30% -> 45%: Layer 4 (Melted cheddar) drapes organically over patty
    tl.fromTo('[data-layer="4"]',
      { y: -260, opacity: 0 },
      { y: 0, opacity: 1, duration: 15, ease: 'power1.inOut' },
      30
    );

    // 45% -> 60%: Layer 5 (Heirloom tomatoes) lands into position
    tl.fromTo('[data-layer="5"]',
      { y: -270, rotate: 4, opacity: 0 },
      { y: 0, rotate: 0, opacity: 1, duration: 15, ease: 'power2.out' },
      45
    );

    // 60% -> 75%: Layer 6 (Caramelized onions & dill pickles) settles
    tl.fromTo('[data-layer="6"]',
      { y: -280, opacity: 0 },
      { y: 0, opacity: 1, duration: 15, ease: 'power2.out' },
      60
    );

    // 75% -> 90%: Layer 7 (Top brioche bun crown) drops with gentle cushion bounce
    tl.fromTo('[data-layer="7"]',
      { y: -380, scaleY: 1.0, opacity: 0 },
      { y: 0, scaleY: 1.0, opacity: 1, duration: 15, ease: 'power2.out' },
      75
    );

    // 90% -> 93%: Bun landing squash compression
    tl.to('[data-layer="7"]', {
      scaleY: 0.96,
      duration: 3,
      ease: 'power1.in'
    }, 90);

    // 93% -> 96%: Bun rebound to natural height
    tl.to('[data-layer="7"]', {
      scaleY: 1.0,
      duration: 3,
      ease: 'power1.out'
    }, 93);

    // 92% -> 100%: Completed burger glow & Final CTA + Search bar reveal
    if (ctaContainer) {
      tl.fromTo(ctaContainer,
        { opacity: 0, y: 35 },
        { opacity: 1, y: 0, duration: 8, ease: 'power2.out' },
        92
      );
    }

    // Interactive address search handler
    var searchInput = document.getElementById('hero-address-input');
    var searchBtn = document.getElementById('btn-hero-search');
    if (searchBtn && searchInput && !searchBtn._hasBurgerListener) {
      searchBtn._hasBurgerListener = true;
      var handleSearch = function () {
        var query = (searchInput.value || '').trim();
        if (query) {
          var globalSearch = document.getElementById('global-search');
          var targetSection = document.getElementById('view-customer') || document.querySelector('.landing-features');
          if (globalSearch && targetSection && targetSection.style && targetSection.style.display !== 'none') {
            globalSearch.value = query;
            globalSearch.dispatchEvent(new Event('input', { bubbles: true }));
            targetSection.scrollIntoView({ behavior: 'smooth' });
            return;
          }
          // Oturumsuz karşılama ekranında (landing.php) arama yapıldığında giriş/keşfet akışına yönlendir
          var loginLink = document.getElementById('landing-btn-login-nav') || document.querySelector('a[href*="login"]');
          var base = (typeof window !== 'undefined' && window.BASE_PATH) ? window.BASE_PATH : '';
          if (loginLink && loginLink.getAttribute('href')) {
            window.location.href = loginLink.getAttribute('href');
          } else {
            window.location.href = base + '/login';
          }
        } else {
          searchInput.focus();
        }
      };
      searchBtn.addEventListener('click', handleSearch);
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') handleSearch();
      });
    }

    // Final CTA button ("Hemen Sipariş Ver", #FF5722)
    var finalCtaBtn = document.getElementById('btn-hero-order');
    if (finalCtaBtn && !finalCtaBtn._hasBurgerListener) {
      finalCtaBtn._hasBurgerListener = true;
      finalCtaBtn.addEventListener('click', function () {
        if (typeof openModal === 'function') {
          openModal('modal-login');
        } else {
          var loginBtn = document.getElementById('landing-btn-login-nav') || document.querySelector('a[href*="login"]');
          if (loginBtn) {
            loginBtn.click();
          } else {
            window.location.href = 'login.html';
          }
        }
      });
    }

    activeTimeline = tl;
    if (typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function') {
      window.requestAnimationFrame(refresh);
    }
    return tl;
  }

  // Recalculate ScrollTrigger measurements after all external resources (fonts, SVGs, images) load
  if (typeof window !== 'undefined') {
    window.addEventListener('load', refresh);
  }

  // Auto-init on DOMContentLoaded or immediately if already loaded
  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initBurgerScrollytelling);
    } else {
      initBurgerScrollytelling();
    }
  }

  // Export for testing and external consumption
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
      MICROCOPY_STEPS: MICROCOPY_STEPS,
      getStepForProgress: getStepForProgress,
      initBurgerScrollytelling: initBurgerScrollytelling,
      refresh: refresh
    };
  } else if (typeof global !== 'undefined') {
    global.BurgerScrollytelling = {
      MICROCOPY_STEPS: MICROCOPY_STEPS,
      getStepForProgress: getStepForProgress,
      init: initBurgerScrollytelling,
      refresh: refresh
    };
  }
})(typeof globalThis !== 'undefined' ? globalThis : this);
