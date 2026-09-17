(() => {
  const videoModal = document.getElementById('playerVideoModal');
  if (!videoModal) return;

  const titleEl = document.getElementById('playerVideoModalTitle');
  const metaEl = videoModal.querySelector('[data-video-modal-meta]');
  const frameEl = videoModal.querySelector('[data-video-modal-frame]');
  const externalEl = videoModal.querySelector('[data-video-modal-external]');
  const controlsEl = videoModal.querySelector('[data-video-modal-controls]');
  const speeds = [0.75, 1, 1.25, 1.5, 1.75, 2];

  let activeVideo = null;

  const youtubeId = (url) => {
    try {
      const parsed = new URL(url);
      if (parsed.hostname.includes('youtu.be')) {
        return parsed.pathname.replace('/', '') || null;
      }
      if (parsed.hostname.includes('youtube.com')) {
        return parsed.searchParams.get('v');
      }
    } catch (_) {
      // ignore
    }
    return null;
  };

  const vimeoId = (url) => {
    const match = String(url).match(/vimeo\.com\/(?:video\/)?(\d+)/i);
    return match ? match[1] : null;
  };

  const setSpeedActive = (rate) => {
    if (!controlsEl) return;
    controlsEl.querySelectorAll('[data-video-speed]').forEach((btn) => {
      const value = parseFloat(btn.getAttribute('data-video-speed') || '1');
      btn.classList.toggle('is-active', Math.abs(value - rate) < 0.001);
    });
  };

  const showNativeControls = (show) => {
    if (!controlsEl) return;
    controlsEl.hidden = !show;
    if (!show) {
      activeVideo = null;
      setSpeedActive(1);
    }
  };

  const setBuffering = (on) => {
    frameEl?.classList.toggle('is-buffering', Boolean(on));
  };

  const closeVideoModal = () => {
    if (activeVideo) {
      activeVideo.pause();
      activeVideo.removeAttribute('src');
      activeVideo.load();
    }
    activeVideo = null;
    setBuffering(false);
    videoModal.hidden = true;
    videoModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('player-video-modal-open');
    if (frameEl) frameEl.innerHTML = '';
    showNativeControls(false);
    if (externalEl) {
      externalEl.hidden = true;
      externalEl.removeAttribute('href');
    }
  };

  const mountNativeVideo = (url) => {
    const wrap = document.createElement('div');
    wrap.className = 'player-video-native';

    const spinner = document.createElement('div');
    spinner.className = 'player-video-buffer';
    spinner.innerHTML = '<span></span><p>Loading video…</p>';

    const video = document.createElement('video');
    video.controls = true;
    video.autoplay = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.setAttribute('playsinline', '');
    video.setAttribute('controlslist', 'nodownload');

    // Explicit source helps browsers pick the right demuxer sooner.
    const source = document.createElement('source');
    source.src = url;
    if (/\.webm(\?|$)/i.test(url)) source.type = 'video/webm';
    else if (/\.ogg(\?|$)/i.test(url)) source.type = 'video/ogg';
    else source.type = 'video/mp4';
    video.appendChild(source);

    const onWaiting = () => setBuffering(true);
    const onReady = () => setBuffering(false);

    video.addEventListener('loadstart', onWaiting);
    video.addEventListener('waiting', onWaiting);
    video.addEventListener('stalled', onWaiting);
    video.addEventListener('canplay', onReady);
    video.addEventListener('playing', onReady);
    video.addEventListener('error', () => {
      setBuffering(false);
      spinner.querySelector('p').textContent = 'Could not load this video. Try Open in new tab.';
      spinner.classList.add('is-error');
    });

    wrap.appendChild(video);
    wrap.appendChild(spinner);
    frameEl.appendChild(wrap);

    activeVideo = video;
    video.playbackRate = 1;
    setBuffering(true);
    showNativeControls(true);
    setSpeedActive(1);

    // Kick playback; ignore autoplay blocks (user already clicked play).
    video.play().catch(() => {});
  };

  const openVideoModal = ({ title, url, meta, source }) => {
    if (!frameEl || !url) return;

    if (titleEl) titleEl.textContent = title || 'Training video';
    if (metaEl) metaEl.textContent = meta || '';

    frameEl.innerHTML = '';
    setBuffering(false);
    showNativeControls(false);
    activeVideo = null;

    const yt = youtubeId(url);
    const vim = vimeoId(url);
    const isFile =
      source === 'upload' ||
      /\.(mp4|webm|ogg|mov)(\?|$)/i.test(url) ||
      url.includes('/storage/shared-videos/') ||
      url.includes('/media/shared-videos/');

    if (isFile) {
      mountNativeVideo(url);
    } else if (yt) {
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.youtube.com/embed/${encodeURIComponent(yt)}?autoplay=1&rel=0`;
      iframe.allow =
        'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.allowFullscreen = true;
      iframe.title = title || 'YouTube video';
      frameEl.appendChild(iframe);
    } else if (vim) {
      const iframe = document.createElement('iframe');
      iframe.src = `https://player.vimeo.com/video/${encodeURIComponent(vim)}?autoplay=1`;
      iframe.allow = 'autoplay; fullscreen; picture-in-picture';
      iframe.allowFullscreen = true;
      iframe.title = title || 'Vimeo video';
      frameEl.appendChild(iframe);
    } else {
      frameEl.innerHTML =
        '<p style="color:#fff;padding:24px;font-size:14px;line-height:1.5;margin:0">This link can’t be embedded. Open it in a new tab to watch.</p>';
    }

    if (externalEl) {
      externalEl.href = url;
      externalEl.hidden = false;
    }

    videoModal.hidden = false;
    videoModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('player-video-modal-open');
  };

  const skipBy = (seconds) => {
    if (!activeVideo || !Number.isFinite(activeVideo.duration)) {
      if (activeVideo) {
        activeVideo.currentTime = Math.max(0, activeVideo.currentTime + seconds);
      }
      return;
    }
    const next = Math.min(activeVideo.duration, Math.max(0, activeVideo.currentTime + seconds));
    activeVideo.currentTime = next;
  };

  const setSpeed = (rate) => {
    if (!activeVideo) return;
    activeVideo.playbackRate = rate;
    setSpeedActive(rate);
  };

  const cycleSpeed = () => {
    if (!activeVideo) return;
    const current = activeVideo.playbackRate || 1;
    const idx = speeds.findIndex((speed) => Math.abs(speed - current) < 0.001);
    const next = speeds[(idx + 1) % speeds.length];
    setSpeed(next);
  };

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-play-video]');
    if (trigger) {
      event.preventDefault();
      event.stopPropagation();
      openVideoModal({
        title: trigger.getAttribute('data-video-title') || '',
        url: trigger.getAttribute('data-video-url') || '',
        meta: trigger.getAttribute('data-video-meta') || '',
        source: trigger.getAttribute('data-video-source') || 'url',
      });
      return;
    }

    const skipBtn = event.target.closest('[data-video-skip]');
    if (skipBtn && videoModal.contains(skipBtn)) {
      event.preventDefault();
      skipBy(parseFloat(skipBtn.getAttribute('data-video-skip') || '0'));
      return;
    }

    const speedBtn = event.target.closest('[data-video-speed]');
    if (speedBtn && videoModal.contains(speedBtn)) {
      event.preventDefault();
      setSpeed(parseFloat(speedBtn.getAttribute('data-video-speed') || '1'));
      return;
    }

    if (event.target.closest('[data-video-modal-close]')) {
      closeVideoModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (videoModal.hidden) return;

    if (event.key === 'Escape') {
      closeVideoModal();
      return;
    }

    if (!activeVideo) return;

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      skipBy(-10);
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      skipBy(10);
    } else if (event.key.toLowerCase() === 's') {
      event.preventDefault();
      cycleSpeed();
    } else if (event.key === ' ' || event.key === 'k') {
      event.preventDefault();
      if (activeVideo.paused) {
        activeVideo.play().catch(() => {});
      } else {
        activeVideo.pause();
      }
    }
  });
})();
