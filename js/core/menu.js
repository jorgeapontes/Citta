/**
 * Menu mobile: abre/fecha, trava o scroll do body, fecha com Escape ou ao
 * clicar num link. Espera o header (injetado por include.js) já existir.
 */
(function () {
  function initMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    if (!toggle || !menu) return;

    var CLOSE_DELAY = 280; // deve acompanhar --dur-base em css/base/tokens.css

    function closeMenu() {
      toggle.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.style.overflow = '';
      window.setTimeout(function () {
        if (toggle.getAttribute('aria-expanded') === 'false') menu.hidden = true;
      }, CLOSE_DELAY);
    }

    function openMenu() {
      toggle.setAttribute('aria-expanded', 'true');
      menu.hidden = false;
      document.body.style.overflow = 'hidden';
      requestAnimationFrame(function () {
        menu.classList.add('is-open');
      });
    }

    toggle.addEventListener('click', () => {
      const isOpen = toggle.getAttribute('aria-expanded') === 'true';
      isOpen ? closeMenu() : openMenu();
    });

    menu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        toggle.focus();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 860) closeMenu();
    });
  }

  document.addEventListener('components:ready', initMenu);
})();
