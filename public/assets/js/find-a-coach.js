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
      const clearButton = document.getElementById('clearFilters');
      const resetButton = document.getElementById('resetFilters');
      const emptyResetButton = document.getElementById('emptyResetFilters');
      const applyButton = document.getElementById('applyFilters');
      const searchForm = document.getElementById('coachSearchForm');
      const resultsSection = document.getElementById('resultsSection');
      const noResults = document.getElementById('noCoachResults');
      const ratingButtons = Array.from(document.querySelectorAll('.rating-filter'));
      const checkboxes = Array.from(document.querySelectorAll('#filtersPanel input[type="checkbox"]'));
      const cards = Array.from(document.querySelectorAll('.coach-result'));
      const minPrice = document.getElementById('minPrice');
      const maxPrice = document.getElementById('maxPrice');
      const distanceRange = document.getElementById('distanceRange');
      const distanceValue = document.getElementById('distanceValue');
      const sportSelect = document.getElementById('sportSelect');
      const sessionTypeSelect = document.getElementById('sessionTypeSelect');
      const whenSelect = document.getElementById('whenSelect');
      const whenDate = document.getElementById('whenDate');
      const chooseDateBtn = document.getElementById('chooseDateBtn');
      const locationInput = document.getElementById('locationInput');
      const useLocationButton = document.getElementById('useLocationBtn');
      const filterTitles = Array.from(document.querySelectorAll('#filtersPanel .filter-title'));

      if (!cards.length) return;

      let searchFilters = {
        sport: '',
        session: '',
        when: '',
        location: ''
      };
      let priceTimer = null;
      let sortByNearest = false;

      filterTitles.forEach((title, index) => {
        const section = title.parentElement;
        if (!section) return;

        const content = Array.from(section.children).filter((item) => item !== title);
        const arrow = title.lastElementChild;
        const controlsId = `filter-section-${index + 1}`;
        const contentRegion = document.createElement('div');
        const expandedMargin = getComputedStyle(title).marginBottom;

        content.forEach((item, contentIndex) => {
          item.id = `${controlsId}-content-${contentIndex + 1}`;
          contentRegion.appendChild(item);
        });
        title.after(contentRegion);

        contentRegion.style.overflow = 'hidden';
        contentRegion.style.maxHeight = 'none';
        contentRegion.style.opacity = '1';
        contentRegion.style.transform = 'translateY(0)';
        contentRegion.style.transition = 'max-height 380ms cubic-bezier(.22,1,.36,1), opacity 240ms ease, transform 380ms cubic-bezier(.22,1,.36,1)';

        title.setAttribute('role', 'button');
        title.setAttribute('tabindex', '0');
        title.setAttribute('aria-expanded', 'true');
        title.setAttribute('aria-controls', content.map((item) => item.id).join(' '));
        title.classList.add('cursor-pointer', 'select-none', 'rounded-md', 'focus:outline-none', 'focus:ring-2', 'focus:ring-brand-red/30');

        if (arrow) {
          arrow.innerHTML = '&#9662;';
          arrow.setAttribute('aria-hidden', 'true');
          arrow.classList.add('inline-block');
          arrow.style.transition = 'transform 380ms cubic-bezier(.22,1,.36,1)';
        }

        title.style.transition = 'margin-bottom 380ms cubic-bezier(.22,1,.36,1)';

        const toggleSection = () => {
          const expanded = title.getAttribute('aria-expanded') === 'true';
          title.setAttribute('aria-expanded', String(!expanded));

          if (expanded) {
            contentRegion.style.maxHeight = `${contentRegion.scrollHeight}px`;
            requestAnimationFrame(() => {
              contentRegion.style.maxHeight = '0px';
              contentRegion.style.opacity = '0';
              contentRegion.style.transform = 'translateY(-6px)';
              title.style.marginBottom = '0px';
            });
          } else {
            contentRegion.style.maxHeight = `${contentRegion.scrollHeight}px`;
            contentRegion.style.opacity = '1';
            contentRegion.style.transform = 'translateY(0)';
            title.style.marginBottom = expandedMargin;
          }

          if (arrow) arrow.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
        };

        contentRegion.addEventListener('transitionend', (event) => {
          if (event.propertyName === 'max-height' && title.getAttribute('aria-expanded') === 'true') {
            contentRegion.style.maxHeight = 'none';
          }
        });

        title.addEventListener('click', toggleSection);
        title.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleSection();
          }
        });
      });

      function selectRating(button) {
        ratingButtons.forEach((item) => {
          item.classList.remove('border-brand-red', 'bg-brand-red', 'text-white');
          item.classList.add(
            'border-zinc-300',
            'bg-white',
            'text-zinc-600',
            'hover:border-brand-red',
            'hover:text-brand-red'
          );
        });

        button.classList.remove(
          'border-zinc-300',
          'bg-white',
          'text-zinc-600',
          'hover:border-brand-red',
          'hover:text-brand-red'
        );
        button.classList.add('border-brand-red', 'bg-brand-red', 'text-white');
      }

      function updateDistanceDisplay() {
        if (!distanceRange || !distanceValue) return;

        const min = Number(distanceRange.min || 0);
        const max = Number(distanceRange.max || 50);
        const value = Number(distanceRange.value);
        const percent = max === min ? 0 : ((value - min) / (max - min)) * 100;
        const thumbOffset = 8 - (percent * 0.16);

        distanceValue.textContent = `${value} mi`;
        distanceValue.style.left = `calc(${percent}% + ${thumbOffset}px)`;
        distanceRange.setAttribute('aria-valuetext', `${value} miles`);
      }

      function selectedValues(category) {
        return checkboxes
          .filter((box) => box.checked && box.dataset.filter === category)
          .map((box) => box.value);
      }

      function cardHasAny(card, category, selected) {
        if (!selected.length) return true;
        const available = String(card.dataset[category] || '')
          .toLowerCase()
          .split(/\s+/)
          .filter(Boolean);
        return selected.some((value) => available.includes(String(value).toLowerCase()));
      }

      function toISODate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
      }

      function presetToDate(value) {
        if (!value) return '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (value === 'today') return toISODate(today);
        if (value === 'tomorrow') {
          today.setDate(today.getDate() + 1);
          return toISODate(today);
        }
        if (value === 'this-weekend') {
          const day = today.getDay();
          const add = day === 6 || day === 0 ? 0 : 6 - day;
          today.setDate(today.getDate() + add);
          return toISODate(today);
        }
        if (value === 'next-week') {
          const add = ((8 - today.getDay()) % 7) || 7;
          today.setDate(today.getDate() + add);
          return toISODate(today);
        }
        return '';
      }

      function selectedSearchDate() {
        return (whenDate?.value || '').trim() || presetToDate(whenSelect?.value || '');
      }

      function coachHasFreeSlot(card, isoDate) {
        if (!isoDate) return true;
        let occupied = [];
        try {
          occupied = JSON.parse(card.dataset.occupied || '[]');
        } catch {
          occupied = [];
        }
        const dayItems = occupied.filter((item) => item && item.date === isoDate);
        if (dayItems.some((item) => !item.time)) return false;
        for (let hour = 8; hour < 19; hour += 1) {
          const start = hour * 60;
          const end = start + 60;
          const clash = dayItems.some((item) => {
            const parts = String(item.time || '').split(':');
            const mins = (Number(parts[0]) || 0) * 60 + (Number(parts[1]) || 0);
            const busyEnd = mins + (Number(item.minutes) || 60);
            return start < busyEnd && mins < end;
          });
          if (!clash) return true;
        }
        return false;
      }

      function persistSearchDraft() {
        const sportOption = sportSelect?.selectedOptions?.[0];
        window.CoachNowSearch?.write({
          location: (locationInput?.value || '').trim(),
          date: selectedSearchDate(),
          sport: sportOption?.text?.trim() && sportSelect.value ? sportOption.text.trim() : '',
          sportSlug: sportSelect?.value || '',
          session: sessionTypeSelect?.value === 'all' ? '' : (sessionTypeSelect?.value || ''),
        });
      }

      function syncSearchFiltersFromHero() {
        searchFilters = {
          sport: sportSelect?.value || '',
          session: sessionTypeSelect?.value === 'all' ? '' : (sessionTypeSelect?.value || ''),
          when: selectedSearchDate(),
          location: (locationInput?.value || '').trim().toLowerCase()
        };
        persistSearchDraft();
      }

      function applyFilters() {
        const activeRating = document.querySelector('.rating-filter.bg-brand-red');
        const selectedRating = Number(activeRating?.dataset.rating || 0);
        let min = minPrice?.value === '' || minPrice?.value == null ? 0 : Number(minPrice.value);
        let max = maxPrice?.value === '' || maxPrice?.value == null ? Infinity : Number(maxPrice.value);
        const maxDistance = Number(distanceRange?.value || 50);

        if (!Number.isFinite(min)) min = 0;
        if (!Number.isFinite(max)) max = Infinity;
        if (min > max) [min, max] = [max, min];

        const experience = selectedValues('experience');
        const age = selectedValues('age');
        const session = selectedValues('session');
        const availability = selectedValues('availability');
        const locationQuery = (searchFilters.location || (locationInput?.value || '').trim().toLowerCase());
        const searchDate = selectedSearchDate();
        let visibleCount = 0;

        cards.forEach((card) => {
          const cardSessions = String(card.dataset.session || '').toLowerCase().split(/\s+/).filter(Boolean);
          const cardWhen = String(card.dataset.when || '').toLowerCase().split(/\s+/).filter(Boolean);
          const cardSports = String(card.dataset.sport || '').toLowerCase().split(/\s+/).filter(Boolean);
          const cardLocation = String(card.dataset.location || '').toLowerCase();
          const price = Number(card.dataset.price || 0);
          const rating = Number(card.dataset.rating || 0);
          const distanceRaw = card.dataset.distance;
          const distance = distanceRaw === '' || distanceRaw == null ? null : Number(distanceRaw);

          const matches =
            price >= min &&
            price <= max &&
            rating >= selectedRating &&
            (distance == null || !Number.isFinite(distance) || distance <= maxDistance) &&
            cardHasAny(card, 'experience', experience) &&
            cardHasAny(card, 'age', age) &&
            cardHasAny(card, 'session', session) &&
            cardHasAny(card, 'availability', availability) &&
            (!searchFilters.sport || cardSports.includes(String(searchFilters.sport).toLowerCase())) &&
            (!searchFilters.session || cardSessions.includes(String(searchFilters.session).toLowerCase())) &&
            (!searchFilters.when || coachHasFreeSlot(card, searchDate)) &&
            (!locationQuery || locationQuery === 'current location' || cardLocation.includes(locationQuery));

          card.hidden = !matches;
          card.style.display = matches ? '' : 'none';
          if (matches) visibleCount += 1;
        });

        if (noResults) noResults.classList.toggle('hidden', visibleCount !== 0);

        if (sortByNearest) {
          const parent = cards[0]?.parentElement;
          if (parent) {
            cards.slice().sort((a, b) => {
              const da = a.dataset.distance === '' ? Number.POSITIVE_INFINITY : Number(a.dataset.distance);
              const db = b.dataset.distance === '' ? Number.POSITIVE_INFINITY : Number(b.dataset.distance);
              return da - db;
            }).forEach((card) => parent.appendChild(card));
          }
        }
      }

      function schedulePriceApply() {
        window.clearTimeout(priceTimer);
        priceTimer = window.setTimeout(applyFilters, 150);
      }

      function resetFilters() {
        checkboxes.forEach((box) => {
          box.checked = false;
        });

        if (minPrice) minPrice.value = '';
        if (maxPrice) maxPrice.value = '';
        if (ratingButtons[0]) selectRating(ratingButtons[0]);
        if (distanceRange) distanceRange.value = '100';
        updateDistanceDisplay();
        if (locationInput) locationInput.value = '';
        if (sportSelect) sportSelect.value = '';
        if (sessionTypeSelect) sessionTypeSelect.value = 'all';
        if (whenSelect) whenSelect.value = '';
        if (whenDate) whenDate.value = '';
        searchFilters = { sport: '', session: '', when: '', location: '' };
        sortByNearest = false;
        applyFilters();
      }

      ratingButtons.forEach((button) => {
        button.addEventListener('click', () => {
          selectRating(button);
          applyFilters();
        });
      });

      checkboxes.forEach((box) => {
        box.addEventListener('change', applyFilters);
      });

      minPrice?.addEventListener('input', schedulePriceApply);
      maxPrice?.addEventListener('input', schedulePriceApply);
      minPrice?.addEventListener('change', applyFilters);
      maxPrice?.addEventListener('change', applyFilters);

      distanceRange?.addEventListener('input', () => {
        updateDistanceDisplay();
        applyFilters();
      });

      clearButton?.addEventListener('click', resetFilters);
      resetButton?.addEventListener('click', resetFilters);
      emptyResetButton?.addEventListener('click', resetFilters);
      applyButton?.addEventListener('click', (event) => {
        event.preventDefault();
        applyFilters();
      });

      updateDistanceDisplay();

      searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        syncSearchFiltersFromHero();
        applyFilters();
        resultsSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });

      sportSelect?.addEventListener('change', () => {
        syncSearchFiltersFromHero();
        applyFilters();
      });
      sessionTypeSelect?.addEventListener('change', () => {
        syncSearchFiltersFromHero();
        applyFilters();
      });
      whenSelect?.addEventListener('change', () => {
        if (whenDate && whenSelect.value) whenDate.value = presetToDate(whenSelect.value);
        syncSearchFiltersFromHero();
        applyFilters();
      });
      chooseDateBtn?.addEventListener('click', () => {
        if (!whenDate) return;
        whenDate.classList.remove('hidden');
        whenDate.showPicker?.();
        whenDate.focus();
      });
      whenDate?.addEventListener('change', () => {
        if (whenSelect && whenDate.value) whenSelect.value = '';
        syncSearchFiltersFromHero();
        applyFilters();
      });
      locationInput?.addEventListener('input', () => {
        searchFilters.location = (locationInput.value || '').trim().toLowerCase();
        persistSearchDraft();
        schedulePriceApply();
      });

      useLocationButton?.addEventListener('click', () => {
        sortByNearest = true;
        const finish = (coords) => {
          if (coords) window.CoachNowSearch?.write({ lat: coords.latitude, lng: coords.longitude });
          if (locationInput) locationInput.placeholder = 'Sorted by nearest listed parks';
          useLocationButton.disabled = false;
          persistSearchDraft();
          applyFilters();
        };

        if (!navigator.geolocation) {
          finish();
          return;
        }

        useLocationButton.disabled = true;
        navigator.geolocation.getCurrentPosition(
          (pos) => finish(pos.coords),
          () => finish(),
          { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
        );
      });

      const params = new URLSearchParams(window.location.search);
      const draft = window.CoachNowSearch?.read() || {};
      if (locationInput && (params.get('location') || draft.location)) {
        locationInput.value = params.get('location') || draft.location;
      }
      if (sportSelect) {
        const slug = params.get('sport') || draft.sportSlug || '';
        if (slug) sportSelect.value = slug;
        else if (draft.sport) {
          const match = [...sportSelect.options].find((opt) => opt.text.trim().toLowerCase() === String(draft.sport).toLowerCase());
          if (match) sportSelect.value = match.value;
        }
      }
      if (sessionTypeSelect && (params.get('session') || draft.session)) {
        sessionTypeSelect.value = params.get('session') || draft.session;
      }
      const incomingDate = params.get('date') || draft.date || '';
      if (incomingDate && whenDate) {
        whenDate.value = incomingDate;
        whenDate.classList.remove('hidden');
        if (whenSelect) whenSelect.value = '';
      }

      syncSearchFiltersFromHero();
      applyFilters();
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