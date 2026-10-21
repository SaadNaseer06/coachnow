(() => {
  // Large-screen text scale used to live only in index.js / find-a-coach.js,
  // so pages like Training Near You kept a tiny header. Own it globally.
  if (window.__coachNowTextScaleBound) return;
  window.__coachNowTextScaleBound = true;

  const HERO_HEADING_SELECTOR = '#hero h1';

  const getScale = () => {
    const width = window.innerWidth;
    if (width >= 2400) return 1.3;
    if (width >= 1920) return 1.22;
    if (width >= 1600) return 1.12;
    return 1;
  };

  const hasDirectText = (el) =>
    Array.from(el.childNodes).some(
      (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0
    );

  const restoreOriginalSize = (el) => {
    if (!el.dataset.bigScreenTextPrepared) return;
    const originalValue = el.dataset.originalInlineFontSize || '';
    const originalPriority = el.dataset.originalInlineFontSizePriority || '';
    if (originalValue) {
      el.style.setProperty('font-size', originalValue, originalPriority);
    } else {
      el.style.removeProperty('font-size');
    }
  };

  const prepareElement = (el) => {
    if (el.dataset.bigScreenTextPrepared) return;
    el.dataset.bigScreenTextPrepared = 'true';
    el.dataset.originalInlineFontSize = el.style.getPropertyValue('font-size') || '';
    el.dataset.originalInlineFontSizePriority = el.style.getPropertyPriority('font-size') || '';
  };

  const applyLargeScreenTextScale = () => {
    const scale = getScale();
    const candidates = Array.from(
      document.body.querySelectorAll(
        'h1,h2,h3,h4,h5,h6,p,a,button,label,input,select,textarea,option,span,li,div'
      )
    ).filter(hasDirectText);

    candidates.forEach((el) => {
      prepareElement(el);
      restoreOriginalSize(el);
    });

    if (scale === 1) return;

    candidates.forEach((el) => {
      if (el.matches(HERO_HEADING_SELECTOR) || el.closest(HERO_HEADING_SELECTOR)) return;
      if (el.closest('.coach-result')) return;

      const baseSize = parseFloat(getComputedStyle(el).fontSize);
      if (!Number.isFinite(baseSize) || baseSize <= 0) return;

      el.style.setProperty('font-size', `${(baseSize * scale).toFixed(2)}px`, 'important');
    });
  };

  let resizeTimer;
  const scheduleScale = () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(applyLargeScreenTextScale, 80);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyLargeScreenTextScale);
  } else {
    applyLargeScreenTextScale();
  }

  window.addEventListener('load', applyLargeScreenTextScale);
  window.addEventListener('resize', scheduleScale);
})();

(() => {
  if (window.__coachNowMotionBound) return;
  window.__coachNowMotionBound = true;

  const root = document.documentElement;
  let sectionObserverStarted = false;

  function startSectionObserver() {
    if (sectionObserverStarted) return;
    sectionObserverStarted = true;

    const sections = document.querySelectorAll('.motion-section');
    const showSection = (section) => section.classList.add('is-visible');

    if (
      window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
      !('IntersectionObserver' in window)
    ) {
      sections.forEach(showSection);
      return;
    }

    const observer = new IntersectionObserver(
      (entries, obs) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          showSection(entry.target);
          obs.unobserve(entry.target);
        });
      },
      { threshold: 0.01, rootMargin: '0px 0px 20% 0px' }
    );

    sections.forEach((section) => {
      const rect = section.getBoundingClientRect();
      // Already on screen (or just below) — don't wait for another scroll tick.
      if (rect.top < window.innerHeight * 1.15) {
        showSection(section);
        return;
      }
      observer.observe(section);
    });

    // Failsafe: never leave footer/content invisible.
    window.setTimeout(() => sections.forEach(showSection), 2500);
  }

  window.startCoachNowPostLoaderMotion = () => {
    root.classList.remove('hero-motion-pending');
    root.classList.add('hero-motion-started');
    startSectionObserver();
  };

  // Pages without a preloader (or if release already finished) still need the reveal.
  const preloader = document.getElementById('coachnowPreloader');
  if (!preloader || preloader.classList.contains('is-finished')) {
    window.startCoachNowPostLoaderMotion();
  }
})();

(() => {
  if (window.coachNowLenis || window.__coachNowLenisBound) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof Lenis === 'undefined') return;

  window.__coachNowLenisBound = true;

  const lenis = new Lenis({
    duration: 1.3,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    wheelMultiplier: 1,
    smoothWheel: true,
    syncTouch: false,
  });

  document.documentElement.classList.add('lenis', 'lenis-smooth');
  window.coachNowLenis = lenis;

  function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
  }

  requestAnimationFrame(raf);

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="#"]');
    if (!link) return;

    const hash = link.getAttribute('href');
    if (!hash || hash === '#') return;

    const target = document.querySelector(hash);
    if (!target) return;

    event.preventDefault();
    const header = document.getElementById('siteHeader');
    const offset = header ? header.offsetHeight + 8 : 80;
    lenis.scrollTo(target, { offset: -offset });
  });
})();

(() => {
  const root = document.documentElement;
  const preloader = document.getElementById('coachnowPreloader');
  if (!preloader || preloader.dataset.managed === '1') return;

  preloader.dataset.managed = '1';
  root.classList.add('preloader-active');

  const shownAt = performance.now();
  const minimumVisibleMs = 550;
  let released = false;

  const releasePreloader = () => {
    if (released) return;
    released = true;

    const wait = Math.max(0, minimumVisibleMs - (performance.now() - shownAt));

    window.setTimeout(() => {
      preloader.classList.add('is-loaded');

      window.setTimeout(() => {
        preloader.classList.add('is-finished');
        root.classList.remove('preloader-active');

        if (typeof window.startCoachNowPostLoaderMotion === 'function') {
          window.startCoachNowPostLoaderMotion();
        }
        if (typeof window.startCoachNowScrollProgress === 'function') {
          window.startCoachNowScrollProgress();
        }
      }, 1600);
    }, wait);
  };

  if (document.readyState === 'complete') {
    releasePreloader();
  } else {
    window.addEventListener('load', releasePreloader, { once: true });
  }

  // Never leave the site blocked behind the splash.
  window.setTimeout(releasePreloader, 4000);
})();

(() => {
  const header = document.getElementById('siteHeader');
  const btn = document.getElementById('mobileMenuBtn');
  const drawer = document.getElementById('mobileMenuDrawer');

  if (header) {
    const updateNavbar = () => {
      header.classList.toggle('nav-scrolled', window.scrollY > 24);
    };

    updateNavbar();
    window.addEventListener('scroll', updateNavbar, { passive: true });
  }

  if (!btn || !drawer) return;

  btn.setAttribute('aria-controls', 'mobileMenuDrawer');
  btn.setAttribute('aria-expanded', 'false');

  const openMenu = () => {
    drawer.classList.remove('hidden');
    drawer.classList.add('is-open');
    btn.classList.add('is-active');
    btn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('mobile-nav-open');
  };

  const closeMenu = () => {
    drawer.classList.add('hidden');
    drawer.classList.remove('is-open');
    btn.classList.remove('is-active');
    btn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('mobile-nav-open');
  };

  btn.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    if (drawer.classList.contains('hidden')) {
      openMenu();
    } else {
      closeMenu();
    }
  });

  drawer.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) closeMenu();
  });
})();
