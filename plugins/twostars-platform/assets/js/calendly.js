(function () {
  function openCalendly(url) {
    if (!url) {
      return;
    }

    if (window.Calendly && typeof window.Calendly.initPopupWidget === 'function') {
      window.Calendly.initPopupWidget({ url: url });
      return;
    }

    window.open(url, '_blank', 'noopener,noreferrer');
  }

  function bindTriggers() {
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-calendly-url]');

      if (!trigger) {
        return;
      }

      event.preventDefault();
      openCalendly(trigger.getAttribute('data-calendly-url'));
    });
  }

  function initBadge() {
    if (!window.twoStarsCalendly || !window.twoStarsCalendly.badge || !window.twoStarsCalendly.badge.enabled) {
      return;
    }

    if (!window.Calendly || typeof window.Calendly.initBadgeWidget !== 'function') {
      window.setTimeout(initBadge, 300);
      return;
    }

    window.Calendly.initBadgeWidget({
      url: window.twoStarsCalendly.badge.url,
      text: window.twoStarsCalendly.badge.text,
      color: window.twoStarsCalendly.badge.color,
      textColor: window.twoStarsCalendly.badge.textColor,
      branding: !!window.twoStarsCalendly.badge.branding
    });
  }

  bindTriggers();
  initBadge();
})();
