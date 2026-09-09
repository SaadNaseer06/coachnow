(() => {
  if (window.__coachNowScrollProgressBound) return;
  window.__coachNowScrollProgressBound = true;

  const root = document.documentElement;
  const bar = document.getElementById('coachnowScrollProgressBar');
  const bubble = document.getElementById('coachnowScrollBubble');
  const percent = bubble ? bubble.querySelector('.scroll-bubble-percent') : null;

  if (!bar || !bubble || !percent) return;

  let ticking = false;
  let stopTimer = null;
  let hasScrolledOnce = false;
  let ready = false;

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
    if (!ready) return;
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
  window.addEventListener('wheel', showBubble, { passive: true });
  window.addEventListener('touchmove', showBubble, { passive: true });

  bubble.addEventListener('click', () => {
    if (window.coachNowLenis) {
      window.coachNowLenis.scrollTo(0);
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  updateProgress();

  const preloader = document.getElementById('coachnowPreloader');
  if (!preloader || preloader.classList.contains('is-finished')) {
    enable();
  } else {
    // Fallback if page scripts never call startCoachNowScrollProgress after the preloader.
    window.setTimeout(() => {
      if (!root.classList.contains('scroll-progress-ready')) {
        enable();
      }
    }, 3500);
  }
})();
