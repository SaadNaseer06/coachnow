(() => {
  if (window.__coachNowScrollProgressBound) return;
  window.__coachNowScrollProgressBound = true;

  const root = document.documentElement;
  const bar = document.getElementById('coachnowScrollProgressBar');
  const bubble = document.getElementById('coachnowScrollBubble');
  const percent = bubble ? bubble.querySelector('.scroll-bubble-percent') : null;
  const rail = document.getElementById('coachnowScrollProgress');

  if (!bar || !bubble || !percent) return;

  const SCROLL_TOLERANCE = 12;

  let ticking = false;
  let stopTimer = null;
  let hasScrolledOnce = false;
  let ready = false;
  let scrollable = false;

  function getMetrics() {
    const doc = document.documentElement;
    const maxScroll = Math.max(0, doc.scrollHeight - window.innerHeight);
    const canScroll = maxScroll > SCROLL_TOLERANCE;
    const progress = canScroll
      ? Math.min(1, Math.max(0, window.scrollY / maxScroll))
      : 0;

    return { maxScroll, canScroll, progress };
  }

  function setScrollable(canScroll) {
    if (scrollable === canScroll) return;
    scrollable = canScroll;
    root.classList.toggle('scroll-progress-active', canScroll);

    if (!canScroll) {
      clearTimeout(stopTimer);
      hasScrolledOnce = false;
      bubble.classList.remove('is-scrolling');
      bubble.classList.add('is-idle');
      bar.style.transform = 'scaleX(0)';
      percent.textContent = '0%';
      if (rail) rail.setAttribute('aria-hidden', 'true');
      bubble.setAttribute('aria-hidden', 'true');
      bubble.tabIndex = -1;
    } else {
      if (rail) rail.setAttribute('aria-hidden', 'false');
      bubble.setAttribute('aria-hidden', 'false');
      bubble.tabIndex = 0;
    }
  }

  function updateProgress() {
    const { canScroll, progress } = getMetrics();
    setScrollable(canScroll);

    if (!canScroll) {
      ticking = false;
      return;
    }

    const rounded = Math.round(progress * 100);
    bar.style.transform = `scaleX(${progress})`;
    percent.textContent = `${rounded}%`;

    const bubbleHeight = bubble.offsetHeight || 48;
    const topInset = window.innerWidth <= 767 ? 68 : 82;
    const bottomInset = window.innerWidth <= 767 ? 18 : 24;
    const travel = Math.max(0, window.innerHeight - topInset - bottomInset - bubbleHeight);
    bubble.style.top = `${topInset + travel * progress}px`;

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

  function showBubble() {
    if (!ready || !getMetrics().canScroll) return;
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

  function enable() {
    if (ready) {
      requestProgressUpdate();
      return;
    }
    ready = true;
    root.classList.add('scroll-progress-ready');
    requestProgressUpdate();
  }

  window.startCoachNowScrollProgress = enable;

  window.addEventListener('scroll', requestProgressUpdate, { passive: true });
  window.addEventListener('resize', requestProgressUpdate, { passive: true });
  window.addEventListener('scroll', showBubble, { passive: true });
  window.addEventListener('wheel', (event) => {
    if (!getMetrics().canScroll) return;
    // Ignore tiny trackpad noise when content isn't meant to move
    if (Math.abs(event.deltaY) < 1 && Math.abs(event.deltaX) < 1) return;
    showBubble();
  }, { passive: true });
  window.addEventListener('touchmove', () => {
    if (!getMetrics().canScroll) return;
    showBubble();
  }, { passive: true });

  bubble.addEventListener('click', () => {
    if (!getMetrics().canScroll) return;
    if (window.coachNowLenis) {
      window.coachNowLenis.scrollTo(0);
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  // Recheck after layout/images settle
  window.addEventListener('load', requestProgressUpdate, { once: true });
  if (typeof ResizeObserver !== 'undefined') {
    const observer = new ResizeObserver(() => requestProgressUpdate());
    observer.observe(document.body);
  }

  updateProgress();

  const preloader = document.getElementById('coachnowPreloader');
  if (!preloader || preloader.classList.contains('is-finished')) {
    enable();
  } else {
    window.setTimeout(() => {
      if (!root.classList.contains('scroll-progress-ready')) {
        enable();
      }
    }, 3500);
  }
})();
