(() => {
  const menuBtn = document.getElementById('adminMenuBtn');
  const backdrop = document.getElementById('adminSidebarBackdrop');

  const closeSidebar = () => document.body.classList.remove('admin-sidebar-open');
  const toggleSidebar = () => document.body.classList.toggle('admin-sidebar-open');

  if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSidebar();
  });

  // Highlight active nav from current filename
  const page = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
  document.querySelectorAll('.admin-nav-link[data-page]').forEach((link) => {
    const target = (link.getAttribute('data-page') || '').toLowerCase();
    if (target === page || (page === '' && target === 'index.html')) {
      link.classList.add('is-active');
    }
  });
})();
