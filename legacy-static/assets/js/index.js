(() => {
      const header = document.getElementById('siteHeader');
      if (!header) return;

      const updateNavbar = () => {
        header.classList.toggle('nav-scrolled', window.scrollY > 24);
      };

      updateNavbar();
      window.addEventListener('scroll', updateNavbar, { passive: true });
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
        el.dataset.originalInlineFontSizePriority =
          el.style.getPropertyPriority('font-size') || '';
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

        // Preserve the specially sized hero headline while increasing all other text.
        candidates.forEach(el => {
          if (el.matches(HERO_HEADING_SELECTOR) || el.closest(HERO_HEADING_SELECTOR)) {
            return;
          }

          const baseSize = parseFloat(getComputedStyle(el).fontSize);
          if (!Number.isFinite(baseSize) || baseSize <= 0) return;

          el.style.setProperty(
            'font-size',
            `${(baseSize * scale).toFixed(2)}px`,
            'important'
          );
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
      const viewport = document.getElementById('testimonialsViewport');
      const track = document.getElementById('testimonialsTrack');
      const prev = document.getElementById('testimonialPrev');
      const next = document.getElementById('testimonialNext');
      if (!viewport || !track || !prev || !next) return;

      const originals = Array.from(track.children);
      if (originals.length < 2) return;

      /*
        Keep several cards preloaded on BOTH sides.
        This prevents an empty edge from ever appearing while the two visible
        cards advance one card at a time.
      */
      const cloneCount = Math.min(3, originals.length);

      const before = originals.slice(-cloneCount).map(card => card.cloneNode(true));
      const after = originals.slice(0, cloneCount).map(card => card.cloneNode(true));

      before.forEach(card => track.insertBefore(card, track.firstChild));
      after.forEach(card => track.appendChild(card));

      let index = cloneCount; // first real card
      let step = 0;
      let autoTimer = null;
      let isAnimating = false;

      const transitionMs = 950;
      const autoDelay = 4200;

      function measure() {
        const cards = track.children;
        if (cards.length < 2) return;
        step = cards[1].offsetLeft - cards[0].offsetLeft;
        jumpTo(index);
      }

      function jumpTo(targetIndex) {
        if (!step) return;
        track.style.transition = 'none';
        track.style.transform = `translate3d(${-targetIndex * step}px,0,0)`;
        track.offsetHeight;
      }

      function slideTo(targetIndex) {
        if (!step || isAnimating) return;
        isAnimating = true;
        index = targetIndex;
        track.style.transition = `transform ${transitionMs}ms cubic-bezier(0.22, 1, 0.36, 1)`;
        track.style.transform = `translate3d(${-index * step}px,0,0)`;
      }

      function nextCard() { slideTo(index + 1); }
      function prevCard() { slideTo(index - 1); }

      track.addEventListener('transitionend', (event) => {
        if (event.propertyName !== 'transform') return;
        isAnimating = false;

        const firstReal = cloneCount;
        const afterLastReal = cloneCount + originals.length;

        /* Forward loop: land on a preloaded clone, then silently remap to
           the matching real-card position while the viewport stays full. */
        if (index >= afterLastReal) {
          index = firstReal + ((index - afterLastReal) % originals.length);
          jumpTo(index);
        }
        /* Backward loop uses the same preloaded-card technique. */
        else if (index < firstReal) {
          const distance = firstReal - index;
          index = afterLastReal - distance;
          jumpTo(index);
        }
      });

      function startAuto() {
        stopAuto();
        autoTimer = setInterval(() => {
          if (!isAnimating) nextCard();
        }, autoDelay);
      }

      function stopAuto() {
        if (autoTimer) {
          clearInterval(autoTimer);
          autoTimer = null;
        }
      }

      next.addEventListener('click', () => {
        nextCard();
        startAuto();
      });

      prev.addEventListener('click', () => {
        prevCard();
        startAuto();
      });

      viewport.addEventListener('mouseenter', stopAuto);
      viewport.addEventListener('mouseleave', startAuto);
      viewport.addEventListener('touchstart', stopAuto, { passive: true });
      viewport.addEventListener('touchend', startAuto, { passive: true });

      let resizeTimer;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(measure, 120);
      });

      window.addEventListener('load', () => {
        measure();
        startAuto();
      });

      measure();
      startAuto();
    })();

(function () {
      const root = document.documentElement;
      const hero = document.getElementById('hero');
      let heroStarted = false;
      let sectionObserverStarted = false;

      function prepareHero() {
        if (!hero) return;

        const badge = hero.querySelector('.uppercase.bg-brand-red');
        const heading = hero.querySelector('h1');
        const subtitle = heading ? heading.nextElementSibling : null;
        const searchForm = document.getElementById('coachSearchForm');
        const searchWrap = searchForm ? searchForm.parentElement : null;
        const searchIntro = searchWrap ? searchWrap.firstElementChild : null;

        const targets = [];
        if (badge) targets.push([badge, 40]);
        if (subtitle) targets.push([subtitle, 170]);

        if (searchIntro) {
          const searchTitle = searchIntro.querySelector('h2');
          const searchCopy = searchIntro.querySelector('p');
          if (searchTitle) targets.push([searchTitle, 300]);
          if (searchCopy) targets.push([searchCopy, 390]);
        }

        if (searchForm) {
          Array.from(searchForm.children).forEach((field, index) => {
            targets.push([field, 500 + (index * 105)]);
          });
        }

        targets.forEach(([el, delay]) => {
          el.classList.add('hero-fade-target');
          el.style.setProperty('--hero-delay', delay + 'ms');
        });

        if (heading) {
          heading.classList.add('hero-type-heading');
          heading.setAttribute('aria-label', 'Find the Right Coach. Train With Confidence.');

          const lines = heading.querySelectorAll('span');
          lines.forEach((line) => {
            const original = line.textContent.trim();
            line.dataset.typeText = original;
            line.textContent = '';
            line.classList.add('hero-type-line');
          });
        }
      }

      function typeGlitchLine(line, speed) {
        return new Promise((resolve) => {
          const text = line.dataset.typeText || '';
          const glitchChars = ['#', '/', '+', '_', '>', '*', '1', '0'];
          let index = 0;

          line.classList.add('is-typing');

          function typeNext() {
            if (index >= text.length) {
              line.textContent = text;
              line.classList.remove('is-glitching');
              window.setTimeout(() => {
                line.classList.remove('is-typing');
                resolve();
              }, 180);
              return;
            }

            const settled = text.slice(0, index);
            const actual = text[index];
            const shouldGlitch = actual !== ' ' && Math.random() > .48;

            if (shouldGlitch) {
              const fake = glitchChars[Math.floor(Math.random() * glitchChars.length)];
              line.textContent = settled + fake;
              line.classList.add('is-glitching');

              window.setTimeout(() => {
                line.textContent = settled + actual;
                line.classList.remove('is-glitching');
                index += 1;
                window.setTimeout(typeNext, speed + Math.random() * 30);
              }, 34 + Math.random() * 34);
            } else {
              line.textContent = settled + actual;
              index += 1;
              window.setTimeout(typeNext, speed + Math.random() * 30);
            }
          }

          typeNext();
        });
      }

      async function startHeroAnimation() {
        if (heroStarted || !hero) return;
        heroStarted = true;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          hero.querySelectorAll('.hero-type-line').forEach((line) => {
            line.textContent = line.dataset.typeText || '';
          });
          root.classList.remove('hero-motion-pending');
          root.classList.add('hero-motion-started');
          return;
        }

        root.classList.remove('hero-motion-pending');
        root.classList.add('hero-motion-started');

        const lines = Array.from(hero.querySelectorAll('.hero-type-line'));
        if (!lines.length) return;

        /* The headline intentionally lasts longer than the rest of the hero reveal. */
        await new Promise((resolve) => window.setTimeout(resolve, 80));
        await typeGlitchLine(lines[0], 62);
        await new Promise((resolve) => window.setTimeout(resolve, 120));
        if (lines[1]) await typeGlitchLine(lines[1], 66);
      }

      function addMotion(el, type, delay = 0, extra = '') {
        if (!el) return;
        el.classList.add('motion-item', type);
        if (extra) extra.split(' ').filter(Boolean).forEach((name) => el.classList.add(name));
        el.style.setProperty('--motion-delay', delay + 'ms');
      }

      function markSection(section) {
        if (section) section.classList.add('motion-section');
        return section;
      }

      function prepareSectionReveals() {
        /* 1) Availability bar: left block slides in, times cascade upward, link enters from the right. */
        const availability = markSection(document.getElementById('availabilityBar'));
        if (availability) {
          const row = availability.querySelector(':scope > div > div');
          if (row) {
            const pieces = Array.from(row.children);
            addMotion(pieces[0], 'motion-from-left', 0);
            addMotion(pieces[1], 'motion-scale', 110);
            if (pieces[2]) {
              Array.from(pieces[2].children).forEach((button, index) => {
                addMotion(button, 'availability-time-motion', 120 + (index * 85));
              });
            }
            addMotion(pieces[3], 'motion-from-right', 500);
          }
        }

        /* 2) Locations: heading first, then cards rise in a stagger. */
        const coaches = markSection(document.getElementById('coaches'));
        if (coaches) {
          const inner = coaches.firstElementChild;
          if (inner) {
            addMotion(inner.children[0], 'motion-soft-up', 0);
            addMotion(inner.children[1], 'motion-soft-up', 80);
            const locationsView = document.getElementById('locationsView');
            const cardsWrap = locationsView ? locationsView.querySelector('#locationsGrid') : null;
            if (cardsWrap) {
              Array.from(cardsWrap.children).forEach((card, index) => {
                addMotion(card, 'motion-pop', 130 + index * 100);
              });
            }
          }
        }

        /* 3) How It Works: step -> arrow -> next step, creating a visual journey. */
        const how = markSection(document.getElementById('how-it-works'));
        if (how) {
          const inner = how.firstElementChild;
          if (inner) {
            addMotion(inner.children[0], 'motion-soft-up', 0);
            const desktop = inner.children[1];
            if (desktop) {
              Array.from(desktop.children).forEach((node, index) => {
                if (index % 2 === 0) {
                  const stepIndex = index / 2;
                  addMotion(node, 'motion-pop', 150 + stepIndex * 330);
                  const iconBox = node.firstElementChild;
                  addMotion(iconBox, 'motion-pop', 190 + stepIndex * 330, 'motion-micro-pop');
                } else {
                  addMotion(node, 'motion-arrow-draw', 355 + ((index - 1) / 2) * 330);
                }
              });
            }
            const mobile = inner.children[2];
            if (mobile) {
              Array.from(mobile.children).forEach((step, index) => {
                addMotion(step, 'motion-pop', 130 + index * 145);
              });
            }
          }
        }

        /* 6) Recruitment CTA: vertical reveal, then two buttons separate outward from center. */
        const cta = how ? how.nextElementSibling : null;
        if (cta && cta.tagName === 'SECTION') {
          markSection(cta);
          const wrap = cta.firstElementChild;
          if (wrap) {
            addMotion(wrap.children[0], 'motion-soft-up', 0);
            addMotion(wrap.children[1], 'motion-soft-up', 110);
            addMotion(wrap.children[2], 'motion-soft-up', 230);
            const buttons = wrap.children[3];
            if (buttons) {
              addMotion(buttons.children[0], 'motion-from-left', 390);
              addMotion(buttons.children[1], 'motion-from-right', 470);
            }
          }
        }

        /* 7) Contact: image/text split, then question card + form from opposite sides. */
        const contact = markSection(document.getElementById('contact'));
        if (contact) {
          const inner = contact.firstElementChild;
          if (inner) {
            const intro = inner.children[0];
            if (intro) {
              addMotion(intro.children[0], 'motion-from-left', 0);
              addMotion(intro.children[1], 'motion-from-right', 130);
            }

            const formRow = inner.children[1];
            if (formRow) {
              addMotion(formRow.children[0], 'motion-from-left', 80);
              addMotion(formRow.children[1], 'motion-from-right', 180);
              const form = formRow.querySelector('#contactForm');
              if (form) {
                const firstGrid = form.firstElementChild;
                if (firstGrid) {
                  Array.from(firstGrid.children).forEach((field, index) => {
                    addMotion(field, 'motion-soft-up', 390 + index * 90);
                  });
                }
                addMotion(form.lastElementChild, 'motion-soft-up', 670);
              }
            }

            /* Testimonials live inside the same section: intro left, belt right. */
            const stories = inner.children[2];
            if (stories) {
              addMotion(stories.children[0], 'motion-from-left', 100);
              addMotion(stories.children[1], 'motion-from-right', 240);
            }
          }
        }

        /* 8) Footer: top row settles in, then columns rise in a gentle stagger. */
        const footer = document.querySelector('footer');
        if (footer) {
          markSection(footer);
          const inner = footer.firstElementChild;
          if (inner) {
            addMotion(inner.children[0], 'motion-soft-up', 0);
            const columns = inner.children[1];
            if (columns) {
              Array.from(columns.children).forEach((col, index) => {
                addMotion(col, 'motion-soft-up', 120 + index * 85);
              });
            }
            addMotion(inner.lastElementChild, 'motion-soft-up', 520);
          }
        }
      }

      function startSectionObserver() {
        if (sectionObserverStarted) return;
        sectionObserverStarted = true;

        const targets = document.querySelectorAll('.motion-section');
        if (!targets.length) return;

        const showSection = (section) => {
          section.classList.add('is-visible');
          /* After the entrance finishes, release transform control so native hover transforms work normally. */
          window.setTimeout(() => {
            section.querySelectorAll('.motion-item').forEach((el) => {
              el.classList.remove(
                'motion-item','motion-from-left','motion-from-right','motion-from-up','motion-soft-up',
                'motion-pop','motion-scale','motion-rotate','motion-arrow-draw','motion-micro-pop','availability-time-motion'
              );
              el.style.removeProperty('--motion-delay');
            });
          }, 1900);
        };

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
          targets.forEach(showSection);
          return;
        }

        const observer = new IntersectionObserver((entries, obs) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            showSection(entry.target);
            obs.unobserve(entry.target);
          });
        }, {
          threshold: .14,
          rootMargin: '0px 0px -8% 0px'
        });

        targets.forEach((el) => observer.observe(el));
      }

      prepareHero();
      prepareSectionReveals();

      window.startCoachNowPostLoaderMotion = function () {
        startHeroAnimation();
        startSectionObserver();
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

        /* Move the bubble from near the top to near the bottom as the page scrolls. */
        const bubbleHeight = bubble.offsetHeight || 48;
        const topInset = window.innerWidth <= 767 ? 68 : 82;
        const bottomInset = window.innerWidth <= 767 ? 18 : 24;
        const travel = Math.max(0, window.innerHeight - topInset - bottomInset - bubbleHeight);
        bubble.style.top = `${topInset + (travel * progress)}px`;

        /* Morph the two right corners as progress changes:
           top-right 0 -> fully rounded, bottom-right fully rounded -> 0. */
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

      bubble.addEventListener('click', function () {
        window.scrollTo({
          top: 0,
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
      });

      window.addEventListener('scroll', requestProgressUpdate, { passive: true });
      window.addEventListener('resize', requestProgressUpdate, { passive: true });
      window.addEventListener('load', requestProgressUpdate, { once: true });
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

      function releasePreloader() {
        const elapsed = performance.now() - shownAt;
        const wait = Math.max(0, minimumVisibleMs - elapsed);

        window.setTimeout(() => {
          preloader.classList.add('is-loaded');

          // Last (left-most) bar finishes at roughly 1.5s.
          window.setTimeout(() => {
            preloader.classList.add('is-finished');
            root.classList.remove('preloader-active');

            // Start all page-entry motion only after the preloader is completely gone.
            if (typeof window.startCoachNowPostLoaderMotion === 'function') {
              window.startCoachNowPostLoaderMotion();
            }

            // Reveal the top scroll progress bar and right-side progress bubble only after loading.
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

      // Safety fallback so the page can never remain blocked by a failed asset.
      window.setTimeout(() => {
        if (!preloader.classList.contains('is-loaded')) releasePreloader();
      }, 5000);
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
  const searchForm = document.getElementById('coachSearchForm');
  if (!searchForm) return;

  const locationInput = document.getElementById('locationInput');
  const locationDropdown = document.getElementById('locationDropdown');
  const useLocationBtn = document.getElementById('useLocationBtn');
  const whenTrigger = document.getElementById('whenTrigger');
  const whenDisplay = document.getElementById('whenDisplay');
  const whenInput = document.getElementById('whenInput');
  const whenCalendar = document.getElementById('whenCalendar');
  const calPrev = document.getElementById('calPrev');
  const calNext = document.getElementById('calNext');
  const calMonthLabel = document.getElementById('calMonthLabel');
  const calDays = document.getElementById('calDays');
  const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
  const toggleFiltersLabel = document.getElementById('toggleFiltersLabel');
  const extraFilters = document.getElementById('extraFilters');

  let viewDate = new Date();
  viewDate.setDate(1);
  let selectedDate = null;

  const locationField = document.querySelector('.hero-field-location');
  const whenField = document.querySelector('.hero-field-when');

  const syncOpenState = () => {
    const locationOpen = locationDropdown && !locationDropdown.classList.contains('hidden');
    const whenOpen = whenCalendar && !whenCalendar.classList.contains('hidden');

    if (locationField) locationField.classList.toggle('is-open', !!locationOpen);
    if (whenField) whenField.classList.toggle('is-open', !!whenOpen);
    searchForm.classList.toggle('has-open-dropdown', !!(locationOpen || whenOpen));
  };

  const closeAllDropdowns = () => {
    if (locationDropdown) {
      locationDropdown.classList.add('hidden');
      if (locationInput) locationInput.setAttribute('aria-expanded', 'false');
    }
    if (whenCalendar) {
      whenCalendar.classList.add('hidden');
      if (whenTrigger) whenTrigger.setAttribute('aria-expanded', 'false');
    }
    syncOpenState();
  };

  const openLocationDropdown = () => {
    if (!locationDropdown) return;
    if (whenCalendar) {
      whenCalendar.classList.add('hidden');
      if (whenTrigger) whenTrigger.setAttribute('aria-expanded', 'false');
    }
    locationDropdown.classList.remove('hidden');
    if (locationInput) locationInput.setAttribute('aria-expanded', 'true');
    filterLocationItems(locationInput ? locationInput.value : '');
    syncOpenState();
  };

  const openWhenCalendar = () => {
    if (!whenCalendar) return;
    if (locationDropdown) {
      locationDropdown.classList.add('hidden');
      if (locationInput) locationInput.setAttribute('aria-expanded', 'false');
    }
    if (extraFilters && !extraFilters.classList.contains('hidden')) {
      extraFilters.classList.add('hidden');
      if (toggleFiltersLabel) toggleFiltersLabel.textContent = 'More filters';
    }
    whenCalendar.classList.remove('hidden');
    if (whenTrigger) whenTrigger.setAttribute('aria-expanded', 'true');
    renderCalendar();
    syncOpenState();
  };

  const filterLocationItems = (query) => {
    if (!locationDropdown) return;
    const q = (query || '').trim().toLowerCase();
    locationDropdown.querySelectorAll('.hero-dropdown-item').forEach((item) => {
      const name = (item.dataset.location || '').toLowerCase();
      const meta = item.textContent.toLowerCase();
      const match = !q || name.includes(q) || meta.includes(q);
      item.classList.toggle('hidden', !match);
    });
  };

  const formatDisplayDate = (date) => {
    return date.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric'
    });
  };

  const toISODate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  const renderCalendar = () => {
    if (!calDays || !calMonthLabel) return;
    const year = viewDate.getFullYear();
    const month = viewDate.getMonth();
    calMonthLabel.textContent = viewDate.toLocaleDateString('en-US', {
      month: 'long',
      year: 'numeric'
    });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevDays = new Date(year, month, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    calDays.innerHTML = '';

    for (let i = 0; i < 42; i++) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'hero-calendar-day';

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
      if (muted) btn.classList.add('is-muted');

      const isPast = cellDate < today;
      if (isPast) btn.disabled = true;

      if (cellDate.getTime() === today.getTime()) btn.classList.add('is-today');
      if (selectedDate && cellDate.getTime() === selectedDate.getTime()) {
        btn.classList.add('is-selected');
      }

      btn.addEventListener('click', () => {
        if (btn.disabled) return;
        selectedDate = cellDate;
        if (whenInput) whenInput.value = toISODate(cellDate);
        if (whenDisplay) {
          whenDisplay.textContent = formatDisplayDate(cellDate);
          whenDisplay.classList.remove('text-zinc-400');
          whenDisplay.classList.add('text-zinc-900');
        }
        closeAllDropdowns();
      });

      calDays.appendChild(btn);
    }
  };

  if (locationInput) {
    locationInput.addEventListener('focus', openLocationDropdown);
    locationInput.addEventListener('click', openLocationDropdown);
    locationInput.addEventListener('input', () => {
      openLocationDropdown();
      filterLocationItems(locationInput.value);
    });
  }

  if (locationDropdown) {
    locationDropdown.querySelectorAll('.hero-dropdown-item').forEach((item) => {
      item.addEventListener('click', () => {
        const name = item.dataset.location || '';
        if (locationInput) locationInput.value = name;
        locationDropdown.querySelectorAll('.hero-dropdown-item').forEach((el) => {
          el.classList.toggle('is-active', el === item);
        });
        closeAllDropdowns();
      });
    });
  }

  if (useLocationBtn && locationInput) {
    useLocationBtn.addEventListener('click', () => {
      locationInput.value = 'Sommers Bend';
      openLocationDropdown();
      filterLocationItems('Sommers Bend');
      closeAllDropdowns();
    });
  }

  if (whenTrigger) {
    whenTrigger.addEventListener('click', () => {
      const isOpen = whenCalendar && !whenCalendar.classList.contains('hidden');
      if (isOpen) closeAllDropdowns();
      else openWhenCalendar();
    });
  }

  if (calPrev) {
    calPrev.addEventListener('click', (e) => {
      e.stopPropagation();
      viewDate.setMonth(viewDate.getMonth() - 1);
      renderCalendar();
    });
  }

  if (calNext) {
    calNext.addEventListener('click', (e) => {
      e.stopPropagation();
      viewDate.setMonth(viewDate.getMonth() + 1);
      renderCalendar();
    });
  }

  if (toggleFiltersBtn && extraFilters) {
    toggleFiltersBtn.addEventListener('click', () => {
      const isHidden = extraFilters.classList.contains('hidden');
      extraFilters.classList.toggle('hidden', !isHidden);
      if (toggleFiltersLabel) {
        toggleFiltersLabel.textContent = isHidden ? 'Hide filters' : 'More filters';
      }
    });
  }

  document.addEventListener('click', (event) => {
    if (!searchForm.contains(event.target)) closeAllDropdowns();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeAllDropdowns();
  });

  searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const params = new URLSearchParams();
    if (locationInput && locationInput.value.trim()) {
      params.set('location', locationInput.value.trim());
    }
    if (whenInput && whenInput.value) params.set('when', whenInput.value);
    const sport = document.getElementById('sportSelect');
    const session = document.getElementById('sessionTypeSelect');
    if (sport) params.set('sport', sport.value);
    if (session) params.set('session', session.value);
    const qs = params.toString();
    window.location.href = qs ? `find-a-coach.html?${qs}` : 'find-a-coach.html';
  });
})();

(() => {
  const LOCATIONS = [
    {
      id: 'sommers-bend',
      name: 'Sommers Bend',
      area: 'Murrieta, CA',
      distance: 1.2,
      image: 'assets/Background (1).png',
      coaches: [
        { name: 'Coach Lee', specialty: 'Private Soccer Training', ages: 'Ages 8–14', price: 50, rating: '4.9', reviews: 86, image: 'assets/Rectangle 8.png' },
        { name: 'Coach Heidi', specialty: 'Small Group Soccer', ages: 'Ages 6–12', price: 40, rating: '5.0', reviews: 64, image: 'assets/Rectangle 8-2.png' },
        { name: 'Coach Gabe', specialty: 'Team Training', ages: 'All ages', price: 45, rating: '4.8', reviews: 112, image: 'assets/Rectangle 8-1.png' }
      ]
    },
    {
      id: 'birdsall',
      name: 'Birdsall',
      area: 'Temecula, CA',
      distance: 2.4,
      image: 'assets/Background.png',
      coaches: [
        { name: 'Coach Alex', specialty: 'Private Soccer Training', ages: 'Ages 8–14', price: 45, rating: '4.9', reviews: 128, image: 'assets/Rectangle 8-1.png' },
        { name: 'Coach Maria', specialty: 'Small Group Soccer', ages: 'Ages 8–14', price: 45, rating: '4.9', reviews: 98, image: 'assets/Rectangle 8-2.png' }
      ]
    },
    {
      id: 'los-alamos',
      name: 'Los Alamos',
      area: 'Murrieta, CA',
      distance: 3.1,
      image: 'assets/hero-bg.png',
      coaches: [
        { name: 'Coach Davos', specialty: 'Private Soccer Training', ages: 'Ages 10–16', price: 55, rating: '4.9', reviews: 74, image: 'assets/Rectangle 8.png' },
        { name: 'Coach Ceja', specialty: 'Performance & Speed', ages: 'Ages 12–18', price: 50, rating: '4.8', reviews: 51, image: 'assets/Rectangle 8-1.png' }
      ]
    },
    {
      id: 'alta-murrieta',
      name: 'Alta Murrieta',
      area: 'Murrieta, CA',
      distance: 4.0,
      image: 'assets/Background (1).png',
      coaches: [
        { name: 'Coach Mike', specialty: 'Team Training', ages: 'Advanced Players', price: 45, rating: '4.9', reviews: 128, image: 'assets/Rectangle 8.png' },
        { name: 'Coach Jordan', specialty: '1-on-1 Skills', ages: 'Ages 7–13', price: 42, rating: '4.7', reviews: 39, image: 'assets/Rectangle 8-2.png' }
      ]
    },
    {
      id: 'temecula-sports',
      name: 'Temecula Sports Park',
      area: 'Temecula, CA',
      distance: 5.2,
      image: 'assets/Background.png',
      coaches: [
        { name: 'Coach Sam', specialty: 'Small Group Soccer', ages: 'Ages 5–10', price: 38, rating: '4.8', reviews: 67, image: 'assets/Rectangle 8-1.png' },
        { name: 'Coach Riley', specialty: 'Private Soccer Training', ages: 'Ages 9–15', price: 48, rating: '5.0', reviews: 91, image: 'assets/Rectangle 8.png' },
        { name: 'Coach Pat', specialty: 'Clinics & Camps', ages: 'All ages', price: 35, rating: '4.6', reviews: 44, image: 'assets/Rectangle 8-2.png' }
      ]
    },
    {
      id: 'california-oaks',
      name: 'California Oaks',
      area: 'Murrieta, CA',
      distance: 6.8,
      image: 'assets/hero-bg.png',
      coaches: [
        { name: 'Coach Nina', specialty: 'Youth Development', ages: 'Ages 6–11', price: 40, rating: '4.9', reviews: 58, image: 'assets/Rectangle 8-2.png' },
        { name: 'Coach Omar', specialty: 'Private Soccer Training', ages: 'Ages 11–17', price: 52, rating: '4.8', reviews: 73, image: 'assets/Rectangle 8-1.png' }
      ]
    }
  ];

  const grid = document.getElementById('locationsGrid');
  const locationsView = document.getElementById('locationsView');
  const coachesView = document.getElementById('locationCoachesView');
  const coachesGrid = document.getElementById('locationCoachesGrid');
  const distanceRange = document.getElementById('homeDistanceRange');
  const distanceValue = document.getElementById('homeDistanceValue');
  const backBtn = document.getElementById('backToLocations');
  const selectedTitle = document.getElementById('selectedLocationTitle');
  const selectedMeta = document.getElementById('selectedLocationMeta');

  if (!grid || !locationsView || !coachesView || !coachesGrid) return;

  const updateDistanceFill = () => {
    if (!distanceRange) return;
    const min = Number(distanceRange.min) || 1;
    const max = Number(distanceRange.max) || 50;
    const val = Number(distanceRange.value) || max;
    const pct = ((val - min) / (max - min)) * 100;
    distanceRange.style.background =
      `linear-gradient(90deg, #da020c 0%, #da020c ${pct}%, #e4e4e7 ${pct}%, #e4e4e7 100%)`;
    if (distanceValue) {
      distanceValue.textContent = val >= 50 ? 'Within 50 miles' : `Within ${val} mile${val === 1 ? '' : 's'}`;
    }
  };

  const coachCardHTML = (coach) => `
    <article class="bg-white rounded-[18px] overflow-hidden border border-zinc-200/80 shadow-[0_5px_18px_rgba(0,0,0,0.04)] hover:-translate-y-1 hover:shadow-[0_12px_28px_rgba(0,0,0,0.08)] transition-all duration-300">
      <div class="relative h-[210px] overflow-hidden bg-zinc-200">
        <img src="${coach.image}" alt="${coach.name}" class="w-full h-full object-cover object-center">
        <div class="absolute top-3 right-3 inline-flex items-center gap-1.5 rounded-[8px] bg-white px-3 py-2 shadow-sm text-[12px] font-semibold text-zinc-900">
          <svg class="w-4 h-4 text-brand-red fill-brand-red" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          ${coach.rating} <span class="text-zinc-400 font-normal">(${coach.reviews})</span>
        </div>
      </div>
      <div class="p-5">
        <h3 class="text-[18px] font-semibold text-[#191615] leading-tight">${coach.name}</h3>
        <p class="text-[12px] text-zinc-500 mt-1.5 mb-4 font-light">${coach.specialty}</p>
        <div class="flex items-center gap-2.5 text-[12px] text-zinc-500 mb-4">
          <svg class="w-4 h-4 text-zinc-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>${coach.ages}</span>
        </div>
        <div class="flex items-baseline gap-1.5 mb-4">
          <span class="text-brand-red font-bold text-[20px]">$${coach.price}</span>
          <span class="text-[11px] text-zinc-400">/ Session</span>
        </div>
        <a href="coach-profile.html" class="w-full h-11 rounded-[10px] border border-zinc-400 bg-white text-[#191615] hover:bg-brand-red hover:border-brand-red hover:text-white inline-flex items-center justify-center gap-2.5 text-[12px] font-medium transition-all duration-200">
          View Profile
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
        </a>
      </div>
    </article>
  `;

  const locationCardHTML = (loc) => `
    <button type="button" class="location-card bg-white rounded-[18px] overflow-hidden border border-zinc-200/80 shadow-[0_5px_18px_rgba(0,0,0,0.04)] hover:-translate-y-1 hover:shadow-[0_12px_28px_rgba(0,0,0,0.08)] transition-all duration-300" data-location-id="${loc.id}">
      <div class="relative h-[180px] overflow-hidden bg-zinc-200">
        <img src="${loc.image}" alt="${loc.name}" class="w-full h-full object-cover object-center">
        <div class="absolute top-3 right-3 inline-flex items-center rounded-[8px] bg-white px-3 py-1.5 shadow-sm text-[12px] font-semibold text-brand-red">
          ${loc.distance} mi
        </div>
      </div>
      <div class="p-5 text-left">
        <h3 class="text-[18px] font-semibold text-[#191615] leading-tight">${loc.name}</h3>
        <p class="text-[12px] text-zinc-500 mt-1.5 mb-4 font-light">${loc.area}</p>
        <div class="flex items-center justify-between gap-3">
          <span class="text-[12px] text-zinc-600">${loc.coaches.length} coach${loc.coaches.length === 1 ? '' : 'es'} available</span>
          <span class="inline-flex items-center gap-1.5 text-[12px] font-medium text-brand-red">
            View coaches
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
          </span>
        </div>
      </div>
    </button>
  `;

  const showLocations = () => {
    const maxDist = distanceRange ? Number(distanceRange.value) : 50;
    const visible = LOCATIONS.filter((loc) => loc.distance <= maxDist);
    grid.innerHTML = visible.length
      ? visible.map(locationCardHTML).join('')
      : `<p class="col-span-full text-center text-sm text-zinc-500 py-10">No parks within this distance. Try increasing the range.</p>`;
    locationsView.classList.remove('hidden');
    coachesView.classList.add('hidden');
  };

  const showCoaches = (locationId) => {
    const loc = LOCATIONS.find((l) => l.id === locationId);
    if (!loc) return;
    if (selectedTitle) selectedTitle.textContent = loc.name;
    if (selectedMeta) {
      selectedMeta.textContent = `${loc.area} · ${loc.distance} miles away · ${loc.coaches.length} coaches`;
    }
    coachesGrid.innerHTML = loc.coaches.map(coachCardHTML).join('');
    locationsView.classList.add('hidden');
    coachesView.classList.remove('hidden');
    coachesView.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  grid.addEventListener('click', (event) => {
    const card = event.target.closest('[data-location-id]');
    if (!card) return;
    showCoaches(card.dataset.locationId);
  });

  if (backBtn) {
    backBtn.addEventListener('click', () => {
      showLocations();
      locationsView.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  if (distanceRange) {
    distanceRange.addEventListener('input', () => {
      updateDistanceFill();
      showLocations();
    });
    updateDistanceFill();
  }

  showLocations();
})();

(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (typeof Lenis === 'undefined') return;

  const lenis = new Lenis({
    duration: 1.3,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    wheelMultiplier: 1,
    smoothWheel: true,
    syncTouch: false
  });

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
})();