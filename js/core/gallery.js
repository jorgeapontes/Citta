/**
 * Galeria da página de imóvel: imagem principal + thumbnails + lightbox,
 * com navegação por teclado e swipe no mobile. Funciona sobre o markup
 * gerado por js/data/render-property.js (ver data-gallery / data-lightbox).
 */
(function () {
  function initGallery(root) {
    if (root.dataset.galleryInit) return;
    root.dataset.galleryInit = 'true';

    const main = root.querySelector('[data-gallery-main]');
    const counter = root.querySelector('[data-gallery-counter]');
    const thumbs = Array.from(root.querySelectorAll('[data-gallery-thumbs] .gallery__thumb'));
    const prevBtn = root.querySelector('[data-gallery-prev]');
    const nextBtn = root.querySelector('[data-gallery-next]');
    const lightbox = document.querySelector('[data-lightbox]');
    if (!main || thumbs.length === 0) return;

    let current = 0;

    function go(index, opts) {
      current = (index + thumbs.length) % thumbs.length;
      const thumb = thumbs[current];
      const full = thumb.getAttribute('data-full');
      const alt = thumb.getAttribute('data-alt') || '';

      main.src = full;
      main.alt = alt;
      if (counter) counter.textContent = `${current + 1} / ${thumbs.length}`;

      thumbs.forEach((t, i) => t.setAttribute('aria-current', String(i === current)));
      const activeThumb = thumbs[current];
      if (activeThumb && activeThumb.scrollIntoView) {
        activeThumb.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: opts && opts.silent ? 'auto' : 'smooth' });
      }

      if (lightbox && !lightbox.hidden) updateLightbox();
    }

    thumbs.forEach((thumb, i) => {
      thumb.addEventListener('click', () => go(i));
    });

    if (prevBtn) prevBtn.addEventListener('click', () => go(current - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => go(current + 1));

    main.addEventListener('click', () => openLightbox());
    main.style.cursor = 'zoom-in';

    // Swipe (mobile)
    let touchStartX = 0;
    root.querySelector('.gallery__main').addEventListener(
      'touchstart',
      (e) => {
        touchStartX = e.changedTouches[0].clientX;
      },
      { passive: true }
    );
    root.querySelector('.gallery__main').addEventListener(
      'touchend',
      (e) => {
        const delta = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(delta) > 40) go(current + (delta < 0 ? 1 : -1));
      },
      { passive: true }
    );

    // ---- Lightbox ----
    const lbImg = lightbox && lightbox.querySelector('[data-lightbox-img]');
    const lbCounter = lightbox && lightbox.querySelector('[data-lightbox-counter]');

    function updateLightbox() {
      const thumb = thumbs[current];
      lbImg.src = thumb.getAttribute('data-full');
      lbImg.alt = thumb.getAttribute('data-alt') || '';
      if (lbCounter) lbCounter.textContent = `${current + 1} / ${thumbs.length}`;
    }

    function openLightbox() {
      if (!lightbox) return;
      updateLightbox();
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
      lightbox.querySelector('[data-lightbox-close]').focus();
    }

    function closeLightbox() {
      if (!lightbox) return;
      lightbox.hidden = true;
      document.body.style.overflow = '';
      main.focus();
    }

    if (lightbox) {
      lightbox.querySelector('[data-lightbox-close]').addEventListener('click', closeLightbox);
      lightbox.querySelector('[data-lightbox-prev]').addEventListener('click', () => go(current - 1));
      lightbox.querySelector('[data-lightbox-next]').addEventListener('click', () => go(current + 1));
      lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
      });

      let lbTouchStartX = 0;
      lightbox.addEventListener('touchstart', (e) => (lbTouchStartX = e.changedTouches[0].clientX), { passive: true });
      lightbox.addEventListener(
        'touchend',
        (e) => {
          const delta = e.changedTouches[0].clientX - lbTouchStartX;
          if (Math.abs(delta) > 40) go(current + (delta < 0 ? 1 : -1));
        },
        { passive: true }
      );

      document.addEventListener('keydown', (e) => {
        if (lightbox.hidden) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') go(current - 1);
        if (e.key === 'ArrowRight') go(current + 1);
      });
    }

    go(0, { silent: true });
  }

  function boot() {
    document.querySelectorAll('[data-gallery]').forEach(initGallery);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
  document.addEventListener('properties:rendered', boot);
})();
