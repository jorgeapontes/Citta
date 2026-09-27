/**
 * Animação de entrada ao rolar a página. Progressive enhancement: a classe
 * "js-reveal-ready" só é adicionada quando JS + IntersectionObserver estão
 * disponíveis, então navegadores sem suporte sempre mostram o conteúdo.
 */
(function () {
  if (!('IntersectionObserver' in window)) return;

  document.documentElement.classList.add('js-reveal-ready');

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -60px 0px' }
  );

  function observe() {
    document.querySelectorAll('[data-reveal]').forEach((el, i) => {
      el.style.setProperty('--reveal-index', i % 8);
      observer.observe(el);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observe);
  } else {
    observe();
  }
  document.addEventListener('components:ready', observe);
  document.addEventListener('properties:rendered', observe);
})();
