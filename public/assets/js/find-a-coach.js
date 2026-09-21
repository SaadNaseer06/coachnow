(() => {
      const root = document.documentElement;
      let sectionObserverStarted = false;

      function startSectionObserver() {
        if (sectionObserverStarted) return;

        sectionObserverStarted = true;

        const sections = document.querySelectorAll('.motion-section');

        const showSection = (section) => {
          section.classList.add('is-visible');
        };

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
          {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px'
          }
        );

        sections.forEach((section) => {
          observer.observe(section);
        });
      }

      window.startCoachNowPostLoaderMotion = () => {
        root.classList.remove('hero-motion-pending');
        root.classList.add('hero-motion-started');

        startSectionObserver();
      };
    })();

(() => {
      if (window.__coachNowScrollProgressBound) {
        window.startCoachNowScrollProgress = window.startCoachNowScrollProgress || (() => {
          document.documentElement.classList.add('scroll-progress-ready');
        });
        return;
      }

      const root = document.documentElement;
      const bar = document.getElementById('coachnowScrollProgressBar');
      const bubble = document.getElementById('coachnowScrollBubble');
      const percent = bubble
        ? bubble.querySelector('.scroll-bubble-percent')
        : null;

      if (!bar || !bubble || !percent) return;

      let ticking = false;

      function updateProgress() {
        const doc = document.documentElement;

        const maxScroll = Math.max(
          1,
          doc.scrollHeight - window.innerHeight
        );

        const progress = Math.min(
          1,
          Math.max(0, window.scrollY / maxScroll)
        );

        percent.textContent = `${Math.round(progress * 100)}%`;

        bar.style.transform = `scaleX(${progress})`;

        const bubbleHeight = bubble.offsetHeight || 48;

        const topInset =
          window.innerWidth <= 767
            ? 68
            : 82;

        const bottomInset =
          window.innerWidth <= 767
            ? 18
            : 24;

        const travel = Math.max(
          0,
          window.innerHeight -
          topInset -
          bottomInset -
          bubbleHeight
        );

        bubble.style.top =
          `${topInset + (travel * progress)}px`;

        const maxRadius = bubbleHeight / 2;

        const topRight =
          maxRadius * progress;

        const bottomRight =
          maxRadius * (1 - progress);

        bubble.style.borderRadius =
          `${maxRadius}px ${topRight}px ${bottomRight}px ${maxRadius}px`;

        ticking = false;
      }

      function requestProgressUpdate() {
        if (ticking) return;

        ticking = true;

        window.requestAnimationFrame(
          updateProgress
        );
      }

      window.addEventListener(
        'scroll',
        requestProgressUpdate,
        {
          passive: true
        }
      );

      window.addEventListener(
        'resize',
        requestProgressUpdate,
        {
          passive: true
        }
      );

      updateProgress();

      window.startCoachNowScrollProgress = () => {
        root.classList.add(
          'scroll-progress-ready'
        );

        requestProgressUpdate();
      };
    })();

(() => {
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

        const elapsed =
          performance.now() - shownAt;

        const wait =
          Math.max(
            0,
            minimumVisibleMs - elapsed
          );

        window.setTimeout(
          () => {
            preloader.classList.add(
              'is-loaded'
            );

            window.setTimeout(
              () => {
                preloader.classList.add(
                  'is-finished'
                );

                root.classList.remove(
                  'preloader-active'
                );

                if (
                  typeof window.startCoachNowPostLoaderMotion === 'function'
                ) {
                  window.startCoachNowPostLoaderMotion();
                }

                if (
                  typeof window.startCoachNowScrollProgress === 'function'
                ) {
                  window.startCoachNowScrollProgress();
                }
              },
              1600
            );
          },
          wait
        );
      }

      if (
        document.readyState === 'complete'
      ) {
        releasePreloader();
      } else {
        window.addEventListener(
          'load',
          releasePreloader,
          {
            once: true
          }
        );
      }

      /* Safety: page can never stay blocked */
      window.setTimeout(
        releasePreloader,
        5000
      );
    })();

(() => {
      if (window.__coachNowScrollProgressBound) return;

      const bubble =
        document.getElementById(
          'coachnowScrollBubble'
        );

      const root =
        document.documentElement;

      if (!bubble) return;

      let stopTimer = null;
      let hasScrolledOnce = false;

      function showBubble() {
        if (
          !root.classList.contains(
            'scroll-progress-ready'
          )
        ) {
          return;
        }

        hasScrolledOnce = true;

        clearTimeout(stopTimer);

        if (
          !bubble.classList.contains(
            'is-scrolling'
          )
        ) {
          bubble.classList.remove(
            'is-idle'
          );

          void bubble.offsetWidth;

          bubble.classList.add(
            'is-scrolling'
          );
        }

        stopTimer =
          window.setTimeout(
            hideBubble,
            650
          );
      }

      function hideBubble() {
        if (
          !hasScrolledOnce ||
          !bubble.classList.contains(
            'is-scrolling'
          )
        ) {
          return;
        }

        bubble.classList.remove(
          'is-scrolling'
        );

        void bubble.offsetWidth;

        bubble.classList.add(
          'is-idle'
        );
      }

      window.addEventListener(
        'scroll',
        showBubble,
        {
          passive: true
        }
      );

      window.addEventListener(
        'wheel',
        showBubble,
        {
          passive: true
        }
      );

      window.addEventListener(
        'touchmove',
        showBubble,
        {
          passive: true
        }
      );
    })();

(() => {
  const searchForm = document.getElementById('coachSearchForm');
  const locationInput = document.getElementById('locationInput');
  const sportSelect = document.getElementById('sportSelect');
  const searchLat = document.getElementById('searchLat');
  const searchLng = document.getElementById('searchLng');
  const useLocationButton = document.getElementById('useLocationBtn');
  const morePanel = document.getElementById('moreFiltersPanel');
  const resultsSection = document.getElementById('resultsSection');
  const resultsMount = document.getElementById('resultsMount');
  const resultsLoader = document.getElementById('resultsLoader');
  const heroSearchBtn = document.getElementById('heroSearchBtn');
  let searchAbort = null;
  let moreFiltersOpen = morePanel && !morePanel.classList.contains('hidden');

  function persistDraft() {
    const sportOption = sportSelect?.selectedOptions?.[0];
    window.CoachNowSearch?.write({
      location: (locationInput?.value || '').trim(),
      sport: sportOption?.text?.trim() && sportSelect?.value ? sportOption.text.trim() : '',
      sportSlug: sportSelect?.value || '',
      lat: searchLat?.value || '',
      lng: searchLng?.value || '',
    });
  }

  function clearCoords() {
    if (searchLat) searchLat.value = '';
    if (searchLng) searchLng.value = '';
  }

  function checkedValues(selector) {
    return [...document.querySelectorAll(selector)]
      .filter((el) => el.checked)
      .map((el) => el.value);
  }

  function buildParams(extra = {}) {
    const params = new URLSearchParams();
    const location = (locationInput?.value || '').trim();
    const sport = sportSelect?.value || '';
    const lat = searchLat?.value || '';
    const lng = searchLng?.value || '';
    const minPrice = document.getElementById('filterMinPrice')?.value || '';
    const maxPrice = document.getElementById('filterMaxPrice')?.value || '';
    const rating = document.getElementById('filterRating')?.value || '0';
    const session = document.getElementById('filterSession')?.value || '';

    if (location) params.set('location', location);
    if (sport) params.set('sport', sport);
    if (lat) params.set('lat', lat);
    if (lng) params.set('lng', lng);
    if (minPrice) params.set('min_price', minPrice);
    if (maxPrice) params.set('max_price', maxPrice);
    if (rating && rating !== '0') params.set('rating', rating);
    if (session) params.set('session', session);
    checkedValues('.filter-experience').forEach((v) => params.append('experience[]', v));
    checkedValues('.filter-age').forEach((v) => params.append('age[]', v));

    Object.entries(extra).forEach(([key, value]) => {
      if (value == null || value === '') params.delete(key);
      else params.set(key, String(value));
    });

    return params;
  }

  function setLoading(isLoading) {
    if (resultsLoader) {
      resultsLoader.hidden = !isLoading;
      resultsLoader.setAttribute('aria-hidden', String(!isLoading));
    }
    resultsSection?.classList.toggle('is-searching', isLoading);
    if (heroSearchBtn) {
      heroSearchBtn.disabled = isLoading;
      heroSearchBtn.classList.toggle('is-busy', isLoading);
    }
    if (searchForm) {
      searchForm.querySelectorAll('input, select, button').forEach((el) => {
        if (el.id === 'useLocationBtn') return;
        if (el.tagName === 'BUTTON' && el.type === 'button' && el.id !== 'heroSearchBtn') return;
      });
    }
  }

  function syncMoreFiltersToggle() {
    const toggle = document.getElementById('toggleMoreFilters');
    const label = document.getElementById('toggleMoreFiltersLabel');
    if (!toggle || !morePanel) return;
    morePanel.classList.toggle('hidden', !moreFiltersOpen);
    toggle.setAttribute('aria-expanded', String(moreFiltersOpen));
    if (label) label.textContent = moreFiltersOpen ? 'Hide filters' : 'More filters';
  }

  async function runSearch({ scroll = true, replaceHistory = true, page } = {}) {
    if (!resultsMount) return;

    const extra = {};
    if (page != null && page !== '' && Number(page) > 1) {
      extra.page = String(page);
    }

    const params = buildParams(extra);
    if (page == null || page === '' || Number(page) <= 1) {
      params.delete('page');
    }
    params.set('ajax', '1');
    persistDraft();
    setLoading(true);

    if (searchAbort) searchAbort.abort();
    searchAbort = new AbortController();

    try {
      const response = await fetch(`/find-a-coach?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: searchAbort.signal,
        credentials: 'same-origin',
      });

      if (!response.ok) throw new Error('Search failed');
      const data = await response.json();
      resultsMount.innerHTML = data.html || '';
      syncMoreFiltersToggle();

      params.delete('ajax');
      const qs = params.toString();
      if (replaceHistory) {
        const nextUrl = qs ? `/find-a-coach?${qs}` : '/find-a-coach';
        window.history.replaceState({}, '', nextUrl);
      }

      if (scroll && resultsSection) {
        resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    } catch (error) {
      if (error?.name === 'AbortError') return;
      console.error(error);
    } finally {
      setLoading(false);
      if (useLocationButton) {
        useLocationButton.disabled = false;
        useLocationButton.classList.remove('is-loading');
        useLocationButton.setAttribute('aria-label', 'Use my location');
      }
    }
  }

  function resetSearch() {
    if (locationInput) locationInput.value = '';
    if (sportSelect) sportSelect.value = '';
    clearCoords();
    const minPrice = document.getElementById('filterMinPrice');
    const maxPrice = document.getElementById('filterMaxPrice');
    const rating = document.getElementById('filterRating');
    const session = document.getElementById('filterSession');
    if (minPrice) minPrice.value = '';
    if (maxPrice) maxPrice.value = '';
    if (rating) rating.value = '0';
    if (session) session.value = '';
    document.querySelectorAll('.filter-experience, .filter-age').forEach((el) => {
      el.checked = false;
    });
    moreFiltersOpen = false;
    window.CoachNowSearch?.write({
      location: '',
      sport: '',
      sportSlug: '',
      lat: '',
      lng: '',
      session: '',
    });
    runSearch({ scroll: true });
  }

  function clearExtraFilters() {
    const minPrice = document.getElementById('filterMinPrice');
    const maxPrice = document.getElementById('filterMaxPrice');
    const rating = document.getElementById('filterRating');
    const session = document.getElementById('filterSession');
    if (minPrice) minPrice.value = '';
    if (maxPrice) maxPrice.value = '';
    if (rating) rating.value = '0';
    if (session) session.value = '';
    document.querySelectorAll('.filter-experience, .filter-age').forEach((el) => {
      el.checked = false;
    });
    runSearch({ scroll: false });
  }

  locationInput?.addEventListener('input', () => {
    clearCoords();
    persistDraft();
  });

  sportSelect?.addEventListener('change', () => {
    persistDraft();
  });

  searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    runSearch({ scroll: true });
  });

  morePanel?.addEventListener('submit', (event) => {
    event.preventDefault();
    runSearch({ scroll: false });
  });

  document.getElementById('clearExtraFilters')?.addEventListener('click', clearExtraFilters);

  resultsSection?.addEventListener('click', (event) => {
    const pageLink = event.target.closest?.('a[data-page]');
    if (pageLink) {
      event.preventDefault();
      const page = pageLink.getAttribute('data-page');
      runSearch({ scroll: true, page });
      return;
    }

    const toggle = event.target.closest?.('#toggleMoreFilters');
    if (toggle) {
      moreFiltersOpen = !moreFiltersOpen;
      syncMoreFiltersToggle();
      return;
    }
    if (event.target.closest?.('#resetSearchBtn, [data-reset-search]')) {
      resetSearch();
    }
  });

  useLocationButton?.addEventListener('click', () => {
    if (!navigator.geolocation) {
      if (locationInput) locationInput.placeholder = 'Location unavailable - enter a city or ZIP';
      return;
    }

    useLocationButton.disabled = true;
    useLocationButton.classList.add('is-loading');
    useLocationButton.setAttribute('aria-label', 'Locating...');
    setLoading(true);

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (searchLat) searchLat.value = String(pos.coords.latitude);
        if (searchLng) searchLng.value = String(pos.coords.longitude);
        if (locationInput) locationInput.value = 'Near me';
        window.CoachNowSearch?.write({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          location: 'Near me',
        });
        persistDraft();
        runSearch({ scroll: true });
      },
      () => {
        setLoading(false);
        useLocationButton.disabled = false;
        useLocationButton.classList.remove('is-loading');
        useLocationButton.setAttribute('aria-label', 'Use my location');
        if (locationInput) locationInput.placeholder = 'Could not get location - enter a city or ZIP';
      },
      { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
    );
  });

  syncMoreFiltersToggle();

  if (resultsSection && new URLSearchParams(window.location.search).has('location')) {
    requestAnimationFrame(() => {
      resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }
})();

(() => {
      const HERO_HEADING_SELECTOR =
        '#hero h1';

      const getScale = () => {
        const width =
          window.innerWidth;

        if (width >= 2400) {
          return 1.30;
        }

        if (width >= 1920) {
          return 1.22;
        }

        if (width >= 1600) {
          return 1.12;
        }

        return 1;
      };

      const hasDirectText = (el) => {
        return Array
          .from(el.childNodes)
          .some(
            node =>
              node.nodeType ===
                Node.TEXT_NODE &&
              node.textContent
                .trim()
                .length > 0
          );
      };

      const restoreOriginalSize = (el) => {
        if (
          !el.dataset.bigScreenTextPrepared
        ) {
          return;
        }

        const originalValue =
          el.dataset.originalInlineFontSize ||
          '';

        const originalPriority =
          el.dataset.originalInlineFontSizePriority ||
          '';

        if (originalValue) {
          el.style.setProperty(
            'font-size',
            originalValue,
            originalPriority
          );
        } else {
          el.style.removeProperty(
            'font-size'
          );
        }
      };

      const prepareElement = (el) => {
        if (
          el.dataset.bigScreenTextPrepared
        ) {
          return;
        }

        el.dataset.bigScreenTextPrepared =
          'true';

        el.dataset.originalInlineFontSize =
          el.style.getPropertyValue(
            'font-size'
          ) || '';

        el.dataset.originalInlineFontSizePriority =
          el.style.getPropertyPriority(
            'font-size'
          ) || '';
      };

      const applyLargeScreenTextScale = () => {
        const scale = getScale();

        const candidates =
          Array.from(
            document.body.querySelectorAll(
              'h1,h2,h3,h4,h5,h6,p,a,button,label,input,select,textarea,option,span,li,div'
            )
          ).filter(
            hasDirectText
          );

        candidates.forEach((el) => {
          prepareElement(el);
          restoreOriginalSize(el);
        });

        if (scale === 1) {
          return;
        }

        candidates.forEach((el) => {
          if (
            el.matches(
              HERO_HEADING_SELECTOR
            ) ||
            el.closest(
              HERO_HEADING_SELECTOR
            ) ||
            el.closest('.coach-result')
          ) {
            return;
          }

          const baseSize =
            parseFloat(
              getComputedStyle(el)
                .fontSize
            );

          if (
            !Number.isFinite(baseSize) ||
            baseSize <= 0
          ) {
            return;
          }

          el.style.setProperty(
            'font-size',
            `${(
              baseSize *
              scale
            ).toFixed(2)}px`,
            'important'
          );
        });
      };

      let resizeTimer;

      const scheduleScale = () => {
        clearTimeout(
          resizeTimer
        );

        resizeTimer =
          setTimeout(
            applyLargeScreenTextScale,
            80
          );
      };

      if (
        document.readyState ===
        'loading'
      ) {
        document.addEventListener(
          'DOMContentLoaded',
          applyLargeScreenTextScale
        );
      } else {
        applyLargeScreenTextScale();
      }

      window.addEventListener(
        'load',
        applyLargeScreenTextScale
      );

      window.addEventListener(
        'resize',
        scheduleScale
      );
    })();

(() => {
      const reduceMotion =
        window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        ).matches;

      if (
        reduceMotion ||
        typeof Lenis === 'undefined'
      ) {
        return;
      }

      const lenis =
        new Lenis({
          duration: 1.3,
          easing: (t) =>
            Math.min(
              1,
              1.001 -
              Math.pow(
                2,
                -10 * t
              )
            ),
          wheelMultiplier: 1,
          smoothWheel: true,
          syncTouch: false
        });

      window.coachNowLenis =
        lenis;

      function raf(time) {
        lenis.raf(time);

        requestAnimationFrame(
          raf
        );
      }

      requestAnimationFrame(
        raf
      );


      /* Internal anchor links */
      document.addEventListener(
        'click',
        (event) => {
          const link =
            event.target.closest(
              'a[href^="#"]'
            );

          if (!link) return;

          const hash =
            link.getAttribute(
              'href'
            );

          if (
            !hash ||
            hash === '#'
          ) {
            return;
          }

          const target =
            document.querySelector(
              hash
            );

          if (!target) return;

          event.preventDefault();

          const header =
            document.getElementById(
              'siteHeader'
            );

          const offset =
            header
              ? header.offsetHeight + 10
              : 0;

          lenis.scrollTo(
            target,
            {
              offset: -offset
            }
          );
        }
      );


      /* Scroll bubble */
      const bubble =
        document.getElementById(
          'coachnowScrollBubble'
        );

      if (bubble) {
        bubble.addEventListener(
          'click',
          () => {
            lenis.scrollTo(0);
          }
        );
      }
    })();

(() => {
      if (window.__coachNowScrollProgressBound) return;

      const bubble =
        document.getElementById(
          'coachnowScrollBubble'
        );

      if (!bubble) return;

      bubble.addEventListener(
        'click',
        () => {
          if (window.coachNowLenis) {
            return;
          }

          window.scrollTo({
            top: 0,
            behavior:
              window.matchMedia(
                '(prefers-reduced-motion: reduce)'
              ).matches
                ? 'auto'
                : 'smooth'
          });
        }
      );
    })();