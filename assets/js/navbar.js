document.addEventListener('DOMContentLoaded', () => {
  const nav       = document.getElementById('main-nav');
  const hamburger = document.getElementById('nav-hamburger');
  const drawer    = document.getElementById('nav-drawer');
  if (!nav) return;

  const onScroll = () => {
    nav.classList.toggle('nav--scrolled', window.scrollY > 60);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  if (!hamburger || !drawer) return;

  const isOpen = () => hamburger.getAttribute('aria-expanded') === 'true';

  const openMenu  = () => { hamburger.setAttribute('aria-expanded', 'true');  drawer.classList.add('open'); };
  const closeMenu = () => { hamburger.setAttribute('aria-expanded', 'false'); drawer.classList.remove('open'); };

  hamburger.addEventListener('click', () => (isOpen() ? closeMenu : openMenu)());

  drawer.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', (e) => {
    if (isOpen() && !nav.contains(e.target) && !drawer.contains(e.target)) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isOpen()) closeMenu();
  });
});
