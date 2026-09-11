(() => {
  'use strict';
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#primary-navigation');
  if (!toggle || !nav) return;
  const mobile = window.matchMedia('(max-width: 900px)');
  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    nav.hidden = mobile.matches && !open;
  };
  const sync = () => {
    toggle.hidden = !mobile.matches;
    setOpen(false);
  };
  toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mobile.matches && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
  nav.addEventListener('click', (event) => { if (event.target.closest('a') && mobile.matches) setOpen(false); });
  mobile.addEventListener('change', sync);
  sync();
})();
