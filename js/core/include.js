/**
 * Injeta componentes HTML compartilhados (header/footer) em qualquer página
 * que tenha um <div data-include="/components/arquivo.html">.
 * Evita duplicar header/footer em cada página (seção 6 do briefing).
 */
(function () {
  async function includeComponents() {
    const nodes = document.querySelectorAll('[data-include]');
    await Promise.all(
      Array.from(nodes).map(async (node) => {
        const url = node.getAttribute('data-include');
        try {
          const res = await fetch(url);
          if (!res.ok) throw new Error('HTTP ' + res.status);
          node.outerHTML = await res.text();
        } catch (err) {
          console.error('Falha ao carregar componente:', url, err);
        }
      })
    );
    document.dispatchEvent(new CustomEvent('components:ready'));
    markActiveNavLink();
    const yearEls = document.querySelectorAll('[data-year]');
    yearEls.forEach((el) => (el.textContent = new Date().getFullYear()));
  }

  function markActiveNavLink() {
    const path = window.location.pathname.replace(/index\.html$/, '');
    document.querySelectorAll('[data-nav-link]').forEach((link) => {
      const target = link.getAttribute('data-nav-link');
      const isHome = target === '/' && path === '/';
      const isMatch = target !== '/' && path.startsWith(target);
      if (isHome || isMatch) {
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  includeComponents();
})();
