(() => {
  const tabs = Array.from(document.querySelectorAll('.profile-tab'));
  const panels = Array.from(document.querySelectorAll('.profile-panel'));

  function activateTab(button, moveFocus = false) {
    const tabName = button.dataset.tab;

    tabs.forEach((tab) => {
      const active = tab === button;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
      tab.setAttribute('tabindex', active ? '0' : '-1');
    });

    panels.forEach((panel) => {
      const active = panel.dataset.tabPanel === tabName;
      panel.hidden = !active;
      if (active && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        panel.animate(
          [
            { opacity: 0, transform: 'translateY(8px)' },
            { opacity: 1, transform: 'translateY(0)' }
          ],
          { duration: 280, easing: 'cubic-bezier(.22,1,.36,1)' }
        );
      }
    });

    if (moveFocus) button.focus();
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      const direction = event.key === 'ArrowRight' ? 1 : -1;
      const next = tabs[(index + direction + tabs.length) % tabs.length];
      activateTab(next, true);
    });
  });
})();

(function () {
  const root = document.documentElement;
  let sectionObserverStarted = false;

  window.startCoachNowPostLoaderMotion = function () {
    root.classList.remove('hero-motion-pending');
    root.classList.add('hero-motion-started');

    if (sectionObserverStarted) return;
    sectionObserverStarted = true;

    const sections = document.querySelectorAll('.motion-section');

    const reveal = (section) => {
      section.classList.add('is-visible');
    };

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
      sections.forEach(reveal);
      return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        reveal(entry.target);
        obs.unobserve(entry.target);
      });
    }, {
      threshold: .12,
      rootMargin: '0px 0px -8% 0px'
    });

    sections.forEach((section) => observer.observe(section));
  };
})();

(function () {
  const root = document.documentElement;
  const bar = document.getElementById('coachnowScrollProgressBar');
  const bubble = document.getElementById('coachnowScrollBubble');
  const percent = bubble ? bubble.querySelector('.scroll-bubble-percent') : null;
  if (!bar || !bubble || !percent) return;

  let ticking = false;

  function updateProgress() {
    const doc = document.documentElement;
    const maxScroll = Math.max(1, doc.scrollHeight - window.innerHeight);
    const progress = Math.min(1, Math.max(0, window.scrollY / maxScroll));
    const rounded = Math.round(progress * 100);

    bar.style.transform = `scaleX(${progress})`;
    percent.textContent = `${rounded}%`;

    const bubbleHeight = bubble.offsetHeight || 48;
    const topInset = window.innerWidth <= 767 ? 68 : 82;
    const bottomInset = window.innerWidth <= 767 ? 18 : 24;
    const travel = Math.max(0, window.innerHeight - topInset - bottomInset - bubbleHeight);
    bubble.style.top = `${topInset + (travel * progress)}px`;

    const maxRadius = bubbleHeight / 2;
    const topRight = maxRadius * progress;
    const bottomRight = maxRadius * (1 - progress);
    bubble.style.borderRadius = `${maxRadius}px ${topRight}px ${bottomRight}px ${maxRadius}px`;

    ticking = false;
  }

  function requestProgressUpdate() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(updateProgress);
  }

  window.addEventListener('scroll', requestProgressUpdate, { passive: true });
  window.addEventListener('resize', requestProgressUpdate, { passive: true });
  updateProgress();

  window.startCoachNowScrollProgress = function () {
    root.classList.add('scroll-progress-ready');
    requestProgressUpdate();
  };
})();

(function () {
  const root = document.documentElement;
  const preloader = document.getElementById('coachnowPreloader');
  if (!preloader) return;

  root.classList.add('preloader-active');
  const shownAt = performance.now();
  const minimumVisibleMs = 550;
  let released = false;

  function releasePreloader() {
    if (released) return;
    released = true;

    const elapsed = performance.now() - shownAt;
    const wait = Math.max(0, minimumVisibleMs - elapsed);

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
  }

  if (document.readyState === 'complete') {
    releasePreloader();
  } else {
    window.addEventListener('load', releasePreloader, { once: true });
  }

  window.setTimeout(releasePreloader, 5000);
})();

(function () {
  const bubble = document.getElementById('coachnowScrollBubble');
  const root = document.documentElement;
  if (!bubble) return;

  let stopTimer = null;
  let hasScrolledOnce = false;

  function showBubble() {
    if (!root.classList.contains('scroll-progress-ready')) return;
    hasScrolledOnce = true;
    clearTimeout(stopTimer);

    if (!bubble.classList.contains('is-scrolling')) {
      bubble.classList.remove('is-idle');
      void bubble.offsetWidth;
      bubble.classList.add('is-scrolling');
    }

    stopTimer = window.setTimeout(hideBubble, 650);
  }

  function hideBubble() {
    if (!hasScrolledOnce || !bubble.classList.contains('is-scrolling')) return;
    bubble.classList.remove('is-scrolling');
    void bubble.offsetWidth;
    bubble.classList.add('is-idle');
  }

  window.addEventListener('scroll', showBubble, { passive: true });
  window.addEventListener('wheel', showBubble, { passive: true });
  window.addEventListener('touchmove', showBubble, { passive: true });
})();

(() => {
  const header = document.getElementById('siteHeader');
  if (!header) return;

  const updateNavbar = () => {
    header.classList.toggle('nav-scrolled', window.scrollY > 24);
  };

  updateNavbar();
  window.addEventListener('scroll', updateNavbar, { passive: true });

  const btn = document.getElementById('mobileMenuBtn');
  const drawer = document.getElementById('mobileMenuDrawer');
  if (btn && drawer) {
    btn.addEventListener('click', () => drawer.classList.toggle('hidden'));
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => drawer.classList.add('hidden')));
  }
})();

(function () {
  const bubble = document.getElementById('coachnowScrollBubble');
  if (bubble) {
    bubble.addEventListener('click', function () {
      if (window.coachNowLenis) {
        window.coachNowLenis.scrollTo(0);
      } else {
        window.scrollTo({top: 0, behavior: 'smooth'});
      }
    });
  }
})();

(function () {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) return;

  const script = document.createElement('script');
  script.src = 'https://unpkg.com/lenis@1.3.26/dist/lenis.min.js';
  script.async = true;
  script.onload = function () {
    if (typeof Lenis === 'undefined') return;

    const lenis = new Lenis({
      duration: 1.3,
      easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      wheelMultiplier: 1,
      smoothWheel: true,
      syncTouch: false
    });

    window.coachNowLenis = lenis;

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    document.addEventListener('click', function (event) {
      const link = event.target.closest('a[href^="#"]');
      if (!link) return;

      const hash = link.getAttribute('href');
      if (!hash || hash === '#') return;

      const target = document.querySelector(hash);
      if (!target) return;

      event.preventDefault();
      const header = document.getElementById('siteHeader');
      const offset = header ? header.offsetHeight + 10 : 0;
      lenis.scrollTo(target, { offset: -offset });
    });
  };
  document.head.appendChild(script);
})();

(() => {
  const HERO_HEADING_SELECTOR = '#hero h1';

  const getScale = () => {
    const width = window.innerWidth;
    if (width >= 2400) return 1.30;
    if (width >= 1920) return 1.22;
    if (width >= 1600) return 1.12;
    return 1;
  };

  const hasDirectText = (el) => {
    return Array.from(el.childNodes).some(
      node => node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0
    );
  };

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

    candidates.forEach(el => {
      prepareElement(el);
      restoreOriginalSize(el);
    });

    if (scale === 1) return;

    candidates.forEach(el => {
      if (el.matches(HERO_HEADING_SELECTOR) || el.closest(HERO_HEADING_SELECTOR)) return;
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
  const field = document.querySelector(".booking-date-field");
  const trigger = document.getElementById("bookingDateTrigger");
  const display = document.getElementById("bookingDateDisplay");
  const input = document.getElementById("bookingDateInput");
  const calendar = document.getElementById("bookingCalendar");
  const calPrev = document.getElementById("bookingCalPrev");
  const calNext = document.getElementById("bookingCalNext");
  const calMonthLabel = document.getElementById("bookingCalMonthLabel");
  const calDays = document.getElementById("bookingCalDays");
  if (!field || !trigger || !calendar || !calDays || !calMonthLabel) return;

  let viewDate = new Date();
  viewDate.setDate(1);
  let selectedDate = null;

  const formatDisplayDate = (date) =>
    date.toLocaleDateString("en-US", { weekday: "short", month: "short", day: "numeric" });

  const toISODate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  };

  const closeCalendar = () => {
    calendar.classList.add("hidden");
    trigger.setAttribute("aria-expanded", "false");
    field.classList.remove("is-open");
  };

  const openCalendar = () => {
    calendar.classList.remove("hidden");
    trigger.setAttribute("aria-expanded", "true");
    field.classList.add("is-open");
    renderCalendar();
  };

  const renderCalendar = () => {
    const year = viewDate.getFullYear();
    const month = viewDate.getMonth();
    calMonthLabel.textContent = viewDate.toLocaleDateString("en-US", {
      month: "long",
      year: "numeric"
    });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevDays = new Date(year, month, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    calDays.innerHTML = "";

    for (let i = 0; i < 42; i++) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "booking-calendar-day";

      let dayNum;
      let cellDate;
      let muted = false;

      if (i < firstDay) {
        dayNum = prevDays - firstDay + i + 1;
        cellDate = new Date(year, month - 1, dayNum);
        muted = true;
      } else if (i >= firstDay + daysInMonth) {
        dayNum = i - firstDay - daysInMonth + 1;
        cellDate = new Date(year, month + 1, dayNum);
        muted = true;
      } else {
        dayNum = i - firstDay + 1;
        cellDate = new Date(year, month, dayNum);
      }

      cellDate.setHours(0, 0, 0, 0);
      btn.textContent = String(dayNum);
      if (muted) btn.classList.add("is-muted");
      if (cellDate < today) btn.disabled = true;
      if (cellDate.getTime() === today.getTime()) btn.classList.add("is-today");
      if (selectedDate && cellDate.getTime() === selectedDate.getTime()) {
        btn.classList.add("is-selected");
      }

      btn.addEventListener("click", () => {
        if (btn.disabled) return;
        selectedDate = cellDate;
        if (input) input.value = toISODate(cellDate);
        if (display) {
          display.textContent = formatDisplayDate(cellDate);
          display.classList.remove("text-zinc-400");
          display.classList.add("text-zinc-900");
        }
        closeCalendar();
      });

      calDays.appendChild(btn);
    }
  };

  trigger.addEventListener("click", () => {
    const isOpen = !calendar.classList.contains("hidden");
    if (isOpen) closeCalendar();
    else openCalendar();
  });

  if (calPrev) {
    calPrev.addEventListener("click", (e) => {
      e.stopPropagation();
      viewDate.setMonth(viewDate.getMonth() - 1);
      renderCalendar();
    });
  }

  if (calNext) {
    calNext.addEventListener("click", (e) => {
      e.stopPropagation();
      viewDate.setMonth(viewDate.getMonth() + 1);
      renderCalendar();
    });
  }

  document.addEventListener("click", (event) => {
    if (!field.contains(event.target)) closeCalendar();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeCalendar();
  });
})();
