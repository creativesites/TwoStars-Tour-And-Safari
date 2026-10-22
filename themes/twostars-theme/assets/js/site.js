(function () {
  document.documentElement.classList.add('twostars-theme-loaded');

  function initMobileNav() {
    var header = document.querySelector('.site-header');
    var toggle = document.querySelector('[data-site-nav-toggle]');
    var panel = document.querySelector('[data-site-nav-panel]');

    if (!header || !toggle || !panel) {
      return;
    }

    function syncScrollState() {
      header.classList.toggle('is-scrolled', window.scrollY > 16);
    }

    toggle.addEventListener('click', function () {
      var isOpen = header.classList.toggle('is-nav-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    panel.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        header.classList.remove('is-nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 720) {
        header.classList.remove('is-nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });

    window.addEventListener('scroll', syncScrollState, { passive: true });
    syncScrollState();
  }

  function initGalleryLightbox() {
    var galleries = document.querySelectorAll('[data-twostars-lightbox-gallery]');

    if (!galleries.length) {
      return;
    }

    var modal = document.createElement('div');
    modal.className = 'twostars-lightbox';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = [
      '<div class="twostars-lightbox__backdrop" data-twostars-lightbox-close></div>',
      '<div class="twostars-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Image gallery">',
      '<button class="twostars-lightbox__close" type="button" aria-label="Close gallery" data-twostars-lightbox-close>&times;</button>',
      '<button class="twostars-lightbox__nav twostars-lightbox__nav--prev" type="button" aria-label="Previous image" data-twostars-lightbox-prev>&lsaquo;</button>',
      '<figure class="twostars-lightbox__figure">',
      '<img class="twostars-lightbox__image" alt="">',
      '<figcaption class="twostars-lightbox__caption"></figcaption>',
      '</figure>',
      '<button class="twostars-lightbox__nav twostars-lightbox__nav--next" type="button" aria-label="Next image" data-twostars-lightbox-next>&rsaquo;</button>',
      '</div>'
    ].join('');

    document.body.appendChild(modal);

    var image = modal.querySelector('.twostars-lightbox__image');
    var caption = modal.querySelector('.twostars-lightbox__caption');
    var activeItems = [];
    var activeIndex = 0;

    function render() {
      if (!activeItems.length) {
        return;
      }

      var current = activeItems[activeIndex];
      image.src = current.src;
      image.alt = current.alt || '';
      caption.textContent = current.caption || '';
      modal.classList.toggle('is-single', activeItems.length === 1);
    }

    function open(items, index) {
      activeItems = items;
      activeIndex = index;
      render();
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('twostars-lightbox-open');
    }

    function close() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('twostars-lightbox-open');
      image.src = '';
    }

    function step(delta) {
      if (activeItems.length < 2) {
        return;
      }

      activeIndex = (activeIndex + delta + activeItems.length) % activeItems.length;
      render();
    }

    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-twostars-lightbox-item]');

      if (trigger) {
        var gallery = trigger.closest('[data-twostars-lightbox-gallery]');

        if (!gallery) {
          return;
        }

        var items = Array.prototype.slice.call(gallery.querySelectorAll('[data-twostars-lightbox-item]')).map(function (item) {
          return {
            src: item.getAttribute('data-twostars-lightbox-src') || '',
            alt: item.getAttribute('data-twostars-lightbox-alt') || '',
            caption: item.getAttribute('data-twostars-lightbox-caption') || ''
          };
        }).filter(function (item) {
          return item.src !== '';
        });

        var index = Array.prototype.indexOf.call(gallery.querySelectorAll('[data-twostars-lightbox-item]'), trigger);

        if (items.length) {
          event.preventDefault();
          open(items, Math.max(index, 0));
        }

        return;
      }

      if (event.target.closest('[data-twostars-lightbox-close]')) {
        close();
        return;
      }

      if (event.target.closest('[data-twostars-lightbox-prev]')) {
        step(-1);
        return;
      }

      if (event.target.closest('[data-twostars-lightbox-next]')) {
        step(1);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (!modal.classList.contains('is-open')) {
        return;
      }

      if (event.key === 'Escape') {
        close();
      } else if (event.key === 'ArrowLeft') {
        step(-1);
      } else if (event.key === 'ArrowRight') {
        step(1);
      }
    });
  }

  function initEventTracking() {
    document.addEventListener('click', function (event) {
      var target = event.target.closest ? event.target.closest('[data-twostars-event]') : null;

      if (!target) {
        return;
      }

      var name = target.getAttribute('data-twostars-event');
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: name, page_path: window.location.pathname });

      if (typeof window.gtag === 'function') {
        window.gtag('event', name, { page_path: window.location.pathname });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initMobileNav();
      initGalleryLightbox();
      initEventTracking();
    });
  } else {
    initMobileNav();
    initGalleryLightbox();
    initEventTracking();
  }
})();
