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

  // Active nav state is set server-side via Blade (routeIs / is-active).
})();
