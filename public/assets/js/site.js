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
